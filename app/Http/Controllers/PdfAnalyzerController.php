<?php

namespace App\Http\Controllers;

use App\Models\EntityBlacklist;
use App\Models\EntityWhitelist;
use App\Models\UserEntityColor;
use App\Services\NlpEntityService;
use App\Services\PdfTextExtractorService;
use App\Services\UnidadActivaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PdfAnalyzerController extends Controller
{
    public function __construct(
        private PdfTextExtractorService $extractor,
        private NlpEntityService        $nlp,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // showForm  — Muestra la pantalla principal del módulo
    // ─────────────────────────────────────────────────────────────────────────
    public function showForm()
    {
        return view('pdf.analyzer', [
            'entityColors' => $this->getUserEntityColors(),
            'entityTypes'  => EntityConfigController::getEntityTypes(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // processPdf  — Recibe el PDF, extrae texto y llama al microservicio NLP
    // ─────────────────────────────────────────────────────────────────────────
    public function processPdf(Request $request)
    {
        $request->validate([
            'pdf'           => ['required', 'file', 'mimes:pdf', 'max:20480'], // 20 MB
            'entity_filter' => ['nullable', 'string'],
        ]);

        $entityFilter = $request->input('entity_filter');

        // 1. Guardar temporalmente
        $path    = $request->file('pdf')->store('pdf-analyzer-tmp', 'local');
        $absPath = Storage::disk('local')->path($path);

        try {
            // 2. Extraer texto
            $text = $this->extractor->extract($absPath);

            // 3. Analizar con NLP
            $result = $this->nlp->analyze($text);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['pdf' => $e->getMessage()]);
        } finally {
            // Eliminar siempre el archivo temporal, ocurra o no excepción
            Storage::disk('local')->delete($path);
        }

        // 3b. Filter by selected entity types if provided
        if ($entityFilter) {
            $allowedTypes = array_map('trim', explode(',', $entityFilter));
            $result = $this->filterByEntityTypes($result, $allowedTypes);
        }

        // 4. Filtrar entidades que estén en la lista negra (blacklist)
        $filtered = $this->filterEntitiesAndHtml(
            $result['entities'] ?? [],
            $result['html']
        );

        // 5. Inyectar entidades de la whitelist que no detectó el NLP
        $final = $this->injectWhitelistEntities($filtered['entities'], $filtered['html'], $text);

        // 6. Guardar el HTML en sesión para anonimización/exportación
        session(['pdf_analyzed_html' => $final['html']]);

        return view('pdf.analyzer', [
            'analyzedHtml'    => $final['html'],
            'entities'        => $final['entities'],
            'groupedEntities' => $this->buildGroupedEntities($final['entities']),
            'entityColors'    => $this->getUserEntityColors(),
            'entityTypes'     => EntityConfigController::getEntityTypes(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // analyzeText  — Recibe texto plano pegado desde el editor y llama al NLP
    // ─────────────────────────────────────────────────────────────────────────
    public function analyzeText(Request $request)
    {
        $request->validate([
            'text'          => ['required', 'string', 'min:10', 'max:200000'],
            'entity_filter' => ['nullable', 'string'],
        ]);

        $text = trim($request->input('text'));
        $entityFilter = $request->input('entity_filter');

        try {
            $result = $this->nlp->analyze($text);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['pdf' => $e->getMessage()]);
        }

        // Filter by selected entity types if provided
        if ($entityFilter) {
            $allowedTypes = array_map('trim', explode(',', $entityFilter));
            $result = $this->filterByEntityTypes($result, $allowedTypes);
        }

        // Filtrar entidades que estén en la lista negra (blacklist)
        $filtered = $this->filterEntitiesAndHtml(
            $result['entities'] ?? [],
            $result['html']
        );

        // Inyectar entidades de la whitelist que no detectó el NLP
        $final = $this->injectWhitelistEntities($filtered['entities'], $filtered['html'], $text);

        // Guardar el HTML en sesión para anonimización/exportación
        session(['pdf_analyzed_html' => $final['html']]);

        return view('pdf.analyzer', [
            'analyzedHtml'    => $final['html'],
            'entities'        => $final['entities'],
            'groupedEntities' => $this->buildGroupedEntities($final['entities']),
            'entityColors'    => $this->getUserEntityColors(),
            'entityTypes'     => EntityConfigController::getEntityTypes(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // addToBlacklist  — Agrega un término detectado a la tabla entity_blacklist
    //
    // Recibe via AJAX (POST JSON):
    //   - term        : texto de la entidad (ej: "Juan García")
    //   - entity_type : código NLP del tipo (PER, ORG, LOC, DATE, DNI, EMAIL, PHONE, MISC)
    //
    // Si el par (term, entity_type) ya existe, reactiva la entrada si estaba inactiva.
    // Devuelve JSON con { success: bool, message: string }.
    // ─────────────────────────────────────────────────────────────────────────
    public function addToBlacklist(Request $request)
    {
        // 1. Validar datos de entrada
        $request->validate([
            'term'        => ['required', 'string', 'max:500'],
            'entity_type' => ['nullable', 'string', 'max:30'],
        ]);

        $term       = trim($request->input('term'));
        $entityType = $request->input('entity_type') ?: null; // null = aplica a todos los tipos
        $unidadId   = Auth::check()
            ? optional(app(UnidadActivaService::class)->get(Auth::user()))->id
            : null;

        try {
            // 2. Buscar si ya existe para evitar duplicados
            //    Si existe y estaba inactivo, lo reactivamos.
            $entry = EntityBlacklist::where('term', $term)
                ->where(function ($q) use ($entityType) {
                    // Comparar entity_type respetando posibles NULLs
                    if ($entityType === null) {
                        $q->whereNull('entity_type');
                    } else {
                        $q->where('entity_type', $entityType);
                    }
                })
                ->when($unidadId, fn($q) => $q->where('unidad_id', $unidadId))
                ->when(!$unidadId, fn($q) => $q->whereNull('unidad_id'))
                ->first();

            if ($entry) {
                // Ya existe: asegurarse de que esté activo
                if (!$entry->active) {
                    $entry->update(['active' => true]);
                }
            } else {
                // 3. Crear nuevo registro en la blacklist
                EntityBlacklist::create([
                    'term'           => $term,
                    'entity_type'    => $entityType,
                    'match_mode'     => 'exact',
                    'case_sensitive' => false,
                    'added_by'       => Auth::check() ? Auth::user()->name : 'usuario',
                    'reason'         => 'Ignorado manualmente desde el analizador de texto.',
                    'active'         => true,
                    'unidad_id'      => $unidadId,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Término \"$term\" agregado a la lista negra. No aparecerá en futuros análisis.",
            ]);

        } catch (\Exception $e) {
            // Registrar el error y devolver respuesta limpia al cliente
            \Log::error('Error al agregar a blacklist: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar en la lista negra. Intente nuevamente.',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // anonimizeEntities  — Reemplaza entidades sensibles con marcadores
    // ─────────────────────────────────────────────────────────────────────────
    public function anonimizeEntities(Request $request)
    {
        $request->validate([
            'html' => ['required', 'string'],
        ]);

        $html = $request->input('html');

        // Mapa de clases CSS → marcadores de anonimización
        $replacements = [
            'person' => '[PERSONA]',
            'dni'    => '[DNI]',
            'email'  => '[EMAIL]',
            'phone'  => '[TELÉFONO]',
        ];

        foreach ($replacements as $class => $marker) {
            // Reemplaza <span class="entity {class}">...</span> por el marcador
            $html = preg_replace(
                '/<span[^>]*class="[^"]*entity[^"]*\b' . $class . '\b[^"]*"[^>]*>.*?<\/span>/is',
                htmlspecialchars($marker),
                $html
            );
        }

        session(['pdf_analyzed_html' => $html]);

        return response()->json(['html' => $html]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // getActiveBlacklist  — Recupera todos los términos activos de la blacklist
    //
    // Devuelve un array listo para ser usado por filterEntitiesAndHtml().
    // Si la base de datos no está disponible, devuelve array vacío sin lanzar
    // excepción (para no interrumpir el análisis principal).
    // ─────────────────────────────────────────────────────────────────────────
    private function getActiveBlacklist(): array
    {
        try {
            return EntityBlacklist::active()
                ->get(['term', 'entity_type', 'match_mode', 'case_sensitive'])
                ->toArray();
        } catch (\Exception $e) {
            // Si PostgreSQL no está disponible, continuar sin filtrar
            \Log::warning('No se pudo cargar la blacklist: ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // filterEntitiesAndHtml  — Elimina entidades blacklisteadas del resultado NLP
    //
    // Recibe el array de entidades y el HTML con spans etiquetados.
    // Para cada entrada activa de la blacklist:
    //   1. Elimina del array $entities las que coinciden con el término.
    //   2. Elimina del HTML los <span class="entity ..."> correspondientes,
    //      reemplazándolos con el texto plano (sin marcar).
    //
    // Sólo implementa match_mode='exact' con case_insensitive por defecto,
    // que es el modo asignado cuando el usuario ignora manualmente una entidad.
    // ─────────────────────────────────────────────────────────────────────────
    private function filterEntitiesAndHtml(array $entities, string $html): array
    {
        // 1. Cargar la blacklist activa desde PostgreSQL
        $blacklist = $this->getActiveBlacklist();

        // Si no hay entradas, devolver sin cambios
        if (empty($blacklist)) {
            return ['entities' => $entities, 'html' => $html];
        }

        // 2. Filtrar el array de entidades
        $entities = array_filter($entities, function (array $ent) use ($blacklist): bool {
            $text  = $ent['text']  ?? '';
            $label = $ent['label'] ?? '';

            foreach ($blacklist as $entry) {
                $matches = $this->termMatchesEntity($text, $label, $entry);
                if ($matches) {
                    return false; // eliminar esta entidad del resultado
                }
            }
            return true; // conservar
        });

        // 3. Limpiar el HTML: reemplazar los spans de entidades blacklisteadas
        //    por su texto plano, para que no aparezcan resaltados.
        foreach ($blacklist as $entry) {
            $term           = $entry['term'];
            $entityType     = $entry['entity_type'] ?? null;
            $caseSensitive  = (bool) ($entry['case_sensitive'] ?? false);

            // Escapar el término para usarlo de forma segura en el patrón regex
            $escapedTerm = preg_quote(htmlspecialchars($term, ENT_QUOTES | ENT_HTML5, 'UTF-8'), '/');

            // Construir el flag de sensibilidad de mayúsculas
            $flags = $caseSensitive ? 'u' : 'iu';

            if ($entityType) {
                // Filtrar sólo spans de ese tipo (data-label="{entityType}")
                $escapedLabel = preg_quote($entityType, '/');
                $pattern = '/<span[^>]*\bclass="entity[^"]*"[^>]*\bdata-label="' . $escapedLabel . '"[^>]*>\s*' . $escapedTerm . '\s*<\/span>/' . $flags;
            } else {
                // Filtrar cualquier span de entidad con ese texto, sin importar el tipo
                $pattern = '/<span[^>]*\bclass="entity[^"]*"[^>]*>' . $escapedTerm . '<\/span>/' . $flags;
            }

            // Reemplazar el span con el texto plano escapado (sin resaltado)
            $html = preg_replace($pattern, htmlspecialchars($term, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $html);
        }

        return [
            'entities' => array_values($entities), // re-indexar
            'html'     => $html,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // termMatchesEntity  — Compara un término de blacklist contra una entidad
    //
    // Sólo implementa match_mode='exact' (modo por defecto del sistema).
    // ─────────────────────────────────────────────────────────────────────────
    private function termMatchesEntity(string $entityText, string $entityLabel, array $entry): bool
    {
        $term          = $entry['term']          ?? '';
        $type          = $entry['entity_type']   ?? null;  // NULL = aplica a todos los tipos
        $caseSensitive = (bool) ($entry['case_sensitive'] ?? false);

        // Si la blacklist especifica tipo, debe coincidir con el label de la entidad
        if ($type !== null && strcasecmp($type, $entityLabel) !== 0) {
            return false;
        }

        // Comparar el texto según sensibilidad al caso
        if ($caseSensitive) {
            return $term === trim($entityText);
        }

        return mb_strtolower(trim($term)) === mb_strtolower(trim($entityText));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // buildGroupedEntities  — Normaliza y agrupa entidades por texto/label
    // ─────────────────────────────────────────────────────────────────────────
    private function buildGroupedEntities(array $entities): array
    {
        $grouped = [];
        $normMap = [];

        $normalize = static function (string $s): string {
            $s = mb_strtolower(trim($s));
            $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
            $s = preg_replace('/[^a-z0-9\s]/i', ' ', $s);
            return trim((string) preg_replace('/\s+/', ' ', $s));
        };

        foreach ($entities as $ent) {
            $text  = trim($ent['text'] ?? '');
            $label = $ent['label'] ?? '';
            if ($text === '') continue;

            $norm = $normalize($text) . '||' . $label;

            if (!isset($normMap[$norm])) {
                $grouped[] = [
                    'text'          => $text,
                    'label'         => $label,
                    'count'         => 0,
                    'positions'     => [],
                    'variants'      => [],
                    'variant_counts'=> [],
                ];
                $normMap[$norm] = count($grouped) - 1;
            }

            $idx = $normMap[$norm];
            $grouped[$idx]['count']++;
            $grouped[$idx]['positions'][] = ['start' => $ent['start'] ?? null, 'end' => $ent['end'] ?? null];

            if (!in_array($text, $grouped[$idx]['variants'], true)) {
                $grouped[$idx]['variants'][] = $text;
                $grouped[$idx]['variant_counts'][$text] = 0;
            }
            $grouped[$idx]['variant_counts'][$text]++;

            $best      = $grouped[$idx]['text'];
            $bestCount = $grouped[$idx]['variant_counts'][$best] ?? 0;
            $thisCount = $grouped[$idx]['variant_counts'][$text];
            if ($thisCount > $bestCount || ($thisCount === $bestCount && mb_strlen($text) > mb_strlen($best))) {
                $grouped[$idx]['text'] = $text;
            }
        }

        foreach ($grouped as &$g) {
            unset($g['variant_counts']);
        }

        return array_values($grouped);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // exportPdf  — Genera un PDF con el texto anonimizado usando DomPDF
    // ─────────────────────────────────────────────────────────────────────────
    public function exportPdf(Request $request)
    {
        $html = session('pdf_analyzed_html', '');

        if (empty($html)) {
            return redirect()->route('pdf-analyzer.form')
                ->withErrors(['pdf' => 'No hay contenido anonimizado para exportar.']);
        }

        // Convertir <br> a saltos de línea antes de quitar etiquetas HTML,
        // para preservar la estructura de párrafos en el PDF exportado.
        $clean = str_ireplace(['<br>', '<br/>', '<br />'], "\n", $html);
        $cleanText = strip_tags($clean);

        $pdfContent = Pdf::loadView('pdf.export', ['text' => $cleanText])
            ->setPaper('a4', 'portrait');

        return $pdfContent->download('documento-anonimizado.pdf');
    }

    // ─────────────────────────────────────────────────────────────────────────    // addToWhitelist  — Agrega un término a la tabla entity_whitelist
    // ───────────────────────────────────────────────────────────────────────────────
    public function addToWhitelist(Request $request)
    {
        $request->validate([
            'term'        => ['required', 'string', 'max:500'],
            'entity_type' => ['nullable', 'string', 'max:30'],
        ]);

        $term       = trim($request->input('term'));
        $entityType = $request->input('entity_type') ?: null;
        $unidadId   = Auth::check()
            ? optional(app(UnidadActivaService::class)->get(Auth::user()))->id
            : null;

        try {
            $entry = EntityWhitelist::where('term', $term)
                ->where(function ($q) use ($entityType) {
                    if ($entityType === null) {
                        $q->whereNull('entity_type');
                    } else {
                        $q->where('entity_type', $entityType);
                    }
                })
                ->when($unidadId, fn($q) => $q->where('unidad_id', $unidadId))
                ->when(!$unidadId, fn($q) => $q->whereNull('unidad_id'))
                ->first();

            if ($entry) {
                if (!$entry->active) {
                    $entry->update(['active' => true]);
                }
            } else {
                EntityWhitelist::create([
                    'term'        => $term,
                    'entity_type' => $entityType,
                    'added_by'    => Auth::check() ? Auth::user()->name : 'usuario',
                    'reason'      => 'Agregado manualmente desde el analizador de texto.',
                    'active'      => true,
                    'unidad_id'   => $unidadId,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Término \"$term\" agregado a la whitelist. Será reconocido en futuros análisis.",
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al agregar a whitelist: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al guardar en la whitelist.'], 500);
        }
    }

    // ───────────────────────────────────────────────────────────────────────────────
    // whitelistIndex  — Lista todas las entradas de la whitelist para gestión
    // ───────────────────────────────────────────────────────────────────────────────
    public function whitelistIndex()
    {
        $unidadId = Auth::check()
            ? optional(app(UnidadActivaService::class)->get(Auth::user()))->id
            : null;

        try {
            $entries = EntityWhitelist::when(
                    $unidadId,
                    fn($q) => $q->where('unidad_id', $unidadId),
                    fn($q) => $q->whereNull('unidad_id')
                )
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            \Log::error('Error al cargar whitelist: ' . $e->getMessage());
            $entries = collect();
        }

        return view('whitelist.index', compact('entries'));
    }

    // ───────────────────────────────────────────────────────────────────────────────
    // whitelistDelete  — Elimina permanentemente una entrada de la whitelist
    // ───────────────────────────────────────────────────────────────────────────────
    public function whitelistDelete(int $id)
    {
        try {
            $entry = EntityWhitelist::findOrFail($id);
            $term  = $entry->term;
            $entry->delete();

            return response()->json([
                'success' => true,
                'message' => "Término \"$term\" eliminado de la whitelist.",
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Entrada no encontrada.'], 404);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar de whitelist: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // getActiveWhitelist  — Recupera los términos activos de la whitelist
    // ─────────────────────────────────────────────────────────────────────────
    private function getActiveWhitelist(): array
    {
        try {
            return EntityWhitelist::active()
                ->get(['term', 'entity_type'])
                ->toArray();
        } catch (\Exception $e) {
            \Log::warning('No se pudo cargar la whitelist: ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // entityTypeToClass  — Convierte un tipo NLP en clase CSS de entidad
    // ─────────────────────────────────────────────────────────────────────────
    private function entityTypeToClass(string $entityType): string
    {
        return match (strtoupper($entityType)) {
            'PER', 'PERSON' => 'person',
            'ORG'           => 'org',
            'LOC', 'GPE'    => 'location',
            'DATE'          => 'date',
            'DNI'           => 'dni',
            'EMAIL'         => 'email',
            'PHONE'         => 'phone',
            default         => 'misc',
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // injectWhitelistEntities  — Agrega al resultado las entidades de la whitelist
    //
    // Para cada término activo en entity_whitelist:
    //   1. Verifica que aparezca en el texto original.
    //   2. Si el NLP ya lo detectó, lo omite (sin duplicar).
    //   3. Si no fue detectado, lo añade al array de entidades Y envuelve sus
    //      ocurrencias en el HTML con el <span class="entity ..."> apropiado,
    //      SOLO en nodos de texto fuera de spans de entidades existentes.
    // ─────────────────────────────────────────────────────────────────────────
    private function injectWhitelistEntities(array $entities, string $html, string $originalText): array
    {
        $whitelist = $this->getActiveWhitelist();
        if (empty($whitelist)) {
            return ['entities' => $entities, 'html' => $html];
        }

        // Mapa de textos ya detectados (en minúsculas) para evitar duplicados
        $existingTexts = array_map(
            fn($e) => mb_strtolower(trim($e['text'] ?? '')),
            $entities
        );

        // Preparar la lista de términos a inyectar
        $toInject = [];
        foreach ($whitelist as $entry) {
            $term = trim($entry['term'] ?? '');
            if ($term === '') continue;

            // El término debe aparecer en el texto fuente
            if (mb_stripos($originalText, $term) === false) continue;

            // No duplicar lo que el NLP ya detectó
            if (in_array(mb_strtolower($term), $existingTexts, true)) continue;

            $entityType = strtoupper($entry['entity_type'] ?? 'MISC') ?: 'MISC';
            $cssClass   = $this->entityTypeToClass($entityType);

            // Añadir al array de entidades
            $entities[]      = ['text' => $term, 'label' => $entityType, 'start' => null, 'end' => null];
            $existingTexts[] = mb_strtolower($term);

            $toInject[] = ['term' => $term, 'type' => $entityType, 'class' => $cssClass];
        }

        if (empty($toInject)) {
            return ['entities' => $entities, 'html' => $html];
        }

        // Inyectar spans en el HTML — solo fuera de spans de entidades ya existentes.
        // Estrategia: dividir el HTML en segmentos "tag" y "texto", rastrear
        // la profundidad de <span> para no tocar el interior de entidades existentes.
        $parts     = preg_split('/(<[^>]+>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result    = [];
        $spanDepth = 0;

        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                // Es un tag HTML
                if (preg_match('/^<span\b/i', $part)) {
                    $spanDepth++;
                } elseif (preg_match('/^<\/span>/i', $part)) {
                    $spanDepth = max(0, $spanDepth - 1);
                }
                $result[] = $part;
            } else {
                // Es texto plano — inyectar solo si no estamos dentro de un span
                if ($spanDepth === 0 && $part !== '') {
                    foreach ($toInject as $inj) {
                        $part = preg_replace_callback(
                            '/(' . preg_quote($inj['term'], '/') . ')/iu',
                            fn($m) => '<span class="entity ' . $inj['class'] . '" data-label="' . $inj['type'] . '">' . $m[0] . '</span>',
                            $part
                        );
                    }
                }
                $result[] = $part;
            }
        }

        $html = implode('', $result);

        return ['entities' => $entities, 'html' => $html];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // blacklistIndex  — Lista todas las entradas de la blacklist para gestión
    // ─────────────────────────────────────────────────────────────────────────
    public function blacklistIndex()
    {
        $unidadId = Auth::check()
            ? optional(app(UnidadActivaService::class)->get(Auth::user()))->id
            : null;

        try {
            $entries = EntityBlacklist::when(
                    $unidadId,
                    fn($q) => $q->where('unidad_id', $unidadId),
                    fn($q) => $q->whereNull('unidad_id')
                )
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            \Log::error('Error al cargar blacklist: ' . $e->getMessage());
            $entries = collect();
        }

        return view('blacklist.index', compact('entries'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // blacklistDelete  — Elimina permanentemente una entrada de la blacklist
    // ─────────────────────────────────────────────────────────────────────────
    public function blacklistDelete(int $id)
    {
        try {
            $entry = EntityBlacklist::findOrFail($id);
            $term  = $entry->term;
            $entry->delete();

            return response()->json([
                'success' => true,
                'message' => "Término \"$term\" eliminado de la blacklist.",
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Entrada no encontrada.'], 404);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar de blacklist: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // filterByEntityTypes — Filters NLP result to only include selected types
    // ─────────────────────────────────────────────────────────────────────────
    private function filterByEntityTypes(array $result, array $allowedTypes): array
    {
        // Normalize allowed types to uppercase
        $allowed = array_map('strtoupper', $allowedTypes);

        // Map synonyms: PERSON→PER, GPE→LOC
        $typeMap = [
            'PER' => ['PER', 'PERSON'],
            'ORG' => ['ORG'],
            'LOC' => ['LOC', 'GPE'],
            'DATE' => ['DATE'],
            'DNI' => ['DNI'],
            'EMAIL' => ['EMAIL'],
            'PHONE' => ['PHONE'],
            'MISC' => ['MISC', 'PATENTE'],
        ];

        // Build a set of all raw labels that are allowed
        $allowedRaw = [];
        foreach ($allowed as $type) {
            if (isset($typeMap[$type])) {
                $allowedRaw = array_merge($allowedRaw, $typeMap[$type]);
            } else {
                $allowedRaw[] = $type;
            }
        }

        // Filter entities
        $entities = array_filter($result['entities'] ?? [], function ($ent) use ($allowedRaw) {
            return in_array(strtoupper($ent['label'] ?? ''), $allowedRaw, true);
        });

        // Remove HTML spans for non-allowed entity types
        $html = $result['html'] ?? '';
        $cssMap = [
            'PER' => 'person', 'PERSON' => 'person',
            'ORG' => 'org',
            'LOC' => 'location', 'GPE' => 'location',
            'DATE' => 'date',
            'DNI' => 'dni',
            'EMAIL' => 'email',
            'PHONE' => 'phone',
            'MISC' => 'misc', 'PATENTE' => 'misc',
        ];

        // Find CSS classes to REMOVE (not in allowed)
        $classesToRemove = [];
        foreach ($cssMap as $rawLabel => $cssClass) {
            if (!in_array($rawLabel, $allowedRaw, true)) {
                $classesToRemove[$cssClass] = true;
            }
        }

        foreach (array_keys($classesToRemove) as $cssClass) {
            $html = preg_replace_callback(
                '/<span[^>]*class="entity\s+' . preg_quote($cssClass, '/') . '"[^>]*>(.*?)<\/span>/is',
                fn($m) => $m[1],
                $html
            );
        }

        return [
            'html' => $html,
            'entities' => array_values($entities),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // getUserEntityColors — Get entity color map for the authenticated user
    // ─────────────────────────────────────────────────────────────────────────
    private function getUserEntityColors(): array
    {
        if (!auth()->check()) {
            return EntityConfigController::getUserColors(0);
        }
        return EntityConfigController::getUserColors(auth()->id());
    }
}
