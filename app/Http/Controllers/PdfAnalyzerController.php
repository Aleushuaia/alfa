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
            'entityColors'    => $this->getUserEntityColors(),
            'entityTypes'     => EntityConfigController::getEntityTypes(),
            'whitelistTerms'  => collect($this->getActiveWhitelist())->pluck('term')->toArray(),
            'blacklistTerms'  => collect($this->getActiveBlacklist())->pluck('term')->toArray(),
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
            'whitelistTerms'  => collect($this->getActiveWhitelist())->pluck('term')->toArray(),
            'blacklistTerms'  => collect($this->getActiveBlacklist())->pluck('term')->toArray(),
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
            'whitelistTerms'  => collect($this->getActiveWhitelist())->pluck('term')->toArray(),
            'blacklistTerms'  => collect($this->getActiveBlacklist())->pluck('term')->toArray(),
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
        $userId     = Auth::id();
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
                    \Log::info('Blacklist: término reactivado', [
                        'term'        => $term,
                        'entity_type' => $entityType,
                        'unidad_id'   => $unidadId,
                        'user_id'     => $userId,
                    ]);
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
                \Log::info('Blacklist: término creado', [
                    'term'        => $term,
                    'entity_type' => $entityType,
                    'unidad_id'   => $unidadId,
                    'user_id'     => $userId,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Término \"$term\" agregado a la lista negra. No aparecerá en futuros análisis.",
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            // Violación de constraint única (PG error 23505): el par (term, entity_type)
            // ya existe en la blacklist (posiblemente con distinto unidad_id).
            // En este caso no es un error real — el término ya está bloqueado.
            if (str_contains($e->getMessage(), '23505')) {
                \Log::warning('Blacklist: violación de unicidad — término ya existe en otra unidad', [
                    'term'        => $term,
                    'entity_type' => $entityType,
                    'unidad_id'   => $unidadId,
                    'user_id'     => $userId,
                    'sqlerror'    => $e->getMessage(),
                ]);
                return response()->json([
                    'success' => true,
                    'message' => "Término \"$term\" ya existe en la lista negra.",
                ]);
            }

            \Log::error('Blacklist: error de base de datos al agregar término', [
                'term'        => $term,
                'entity_type' => $entityType,
                'unidad_id'   => $unidadId,
                'user_id'     => $userId,
                'exception'   => get_class($e),
                'message'     => $e->getMessage(),
                'sqlerror'    => $e->getPrevious()?->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error de base de datos al guardar en la lista negra. Intente nuevamente.',
            ], 500);

        } catch (\Exception $e) {
            \Log::error('Blacklist: error inesperado al agregar término', [
                'term'        => $term,
                'entity_type' => $entityType,
                'unidad_id'   => $unidadId,
                'user_id'     => $userId,
                'exception'   => get_class($e),
                'message'     => $e->getMessage(),
                'file'        => $e->getFile() . ':' . $e->getLine(),
            ]);

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
            $unidadId = Auth::check()
                ? optional(app(UnidadActivaService::class)->get(Auth::user()))->id
                : null;

            return EntityBlacklist::active()
                ->where(function ($q) use ($unidadId) {
                    if ($unidadId) {
                        $q->where('unidad_id', $unidadId);
                    } else {
                        $q->whereNull('unidad_id');
                    }
                })
                ->get(['term', 'entity_type', 'match_mode', 'case_sensitive'])
                ->toArray();
        } catch (\Exception $e) {
            // Si PostgreSQL no está disponible, continuar sin filtrar
            \Log::warning('No se pudo cargar la blacklist: ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // normalizeForMatch  — Normaliza un string para comparaciones de matching:
    //   minúsculas + elimina diacríticos (NFD) + colapsa espacios múltiples.
    //   Solo para uso interno en matching; nunca para mostrar al usuario.
    // ─────────────────────────────────────────────────────────────────────────
    private static function normalizeForMatch(string $s): string
    {
        if (class_exists('Normalizer')) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_KD) ?: $s;
        }
        $s = preg_replace('/\p{M}/u', '', $s);
        $s = mb_strtolower($s);
        return trim((string) preg_replace('/\s+/u', ' ', $s));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // buildFlexiblePattern  — Genera un patrón regex que detecta el término
    //   independientemente de: acentos/diacríticos, mayúsculas/minúsculas
    //   y espacios extra entre palabras. Usar siempre con el flag /iu.
    // ─────────────────────────────────────────────────────────────────────────
    private static function buildFlexiblePattern(string $term): string
    {
        static $ACCENT_MAP = [
            'a' => 'aáàâãäåā', 'e' => 'eéèêëē', 'i' => 'iíìîïī',
            'o' => 'oóòôõöø',  'u' => 'uúùûüū', 'n' => 'nñ',
            'c' => 'cç',       'y' => 'yý',      's' => 'sśš',
            'z' => 'zźž',      'l' => 'lł',
        ];

        $normalized   = self::normalizeForMatch($term);
        $wordPatterns = [];

        foreach (explode(' ', $normalized) as $word) {
            if ($word === '') continue;
            $charPat = '';
            foreach (preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) as $char) {
                $charPat .= isset($ACCENT_MAP[$char])
                    ? '[' . $ACCENT_MAP[$char] . ']'
                    : preg_quote($char, '/');
            }
            $wordPatterns[] = $charPat;
        }

        return implode('\s+', $wordPatterns);
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

        // 3. Limpiar el HTML: reemplazar los spans blacklisteados por texto plano.
        //    Patrón flexible: insensible a acentos, mayúsculas y espacios extra.
        foreach ($blacklist as $entry) {
            $term          = $entry['term'];
            $entityType    = $entry['entity_type'] ?? null;
            $caseSensitive = (bool) ($entry['case_sensitive'] ?? false);

            if ($caseSensitive) {
                // Modo sensible al caso: solo normalizar espacios múltiples
                $wordParts    = preg_split('/\s+/u', trim($term), -1, PREG_SPLIT_NO_EMPTY);
                $innerPattern = implode('\s+', array_map(
                    fn($w) => preg_quote(htmlspecialchars($w, ENT_QUOTES | ENT_HTML5, 'UTF-8'), '/'),
                    $wordParts
                ));
                $flags = 'u';
            } else {
                // Modo insensible: acentos + mayúsculas + espacios extra
                $innerPattern = self::buildFlexiblePattern($term);
                $flags        = 'iu';
            }

            if ($entityType) {
                $escapedLabel = preg_quote($entityType, '/');
                $pattern = '/<span[^>]*\bclass="entity[^"]*"[^>]*\bdata-label="' . $escapedLabel . '"[^>]*>\s*' . $innerPattern . '\s*<\/span>/' . $flags;
            } else {
                $pattern = '/<span[^>]*\bclass="entity[^"]*"[^>]*>\s*' . $innerPattern . '\s*<\/span>/' . $flags;
            }

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
        $type          = $entry['entity_type']   ?? null;
        $caseSensitive = (bool) ($entry['case_sensitive'] ?? false);

        if ($type !== null && strcasecmp($type, $entityLabel) !== 0) {
            return false;
        }

        if ($caseSensitive) {
            // Sensible al caso: solo normalizar espacios múltiples
            $normTerm = trim((string) preg_replace('/\s+/u', ' ', $term));
            $normText = trim((string) preg_replace('/\s+/u', ' ', $entityText));
            return $normTerm === $normText;
        }

        // Default: insensible a mayúsculas, acentos y espacios extra
        return self::normalizeForMatch($term) === self::normalizeForMatch($entityText);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // buildGroupedEntities  — Normaliza y agrupa entidades por texto/label
    // ─────────────────────────────────────────────────────────────────────────
    private function buildGroupedEntities(array $entities): array
    {
        $grouped = [];
        $normMap = [];

        $normalize = static fn(string $s): string => self::normalizeForMatch($s);

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

    // ─────────────────────────────────────────────────────────────────────────
    // removeFromWhitelist  — Elimina un término de la whitelist por nombre
    //
    // Recibe via AJAX (POST JSON):
    //   - term : texto de la entidad a eliminar (búsqueda exacta case-insensitive)
    //
    // Elimina todas las entradas de la unidad activa con ese término.
    // ─────────────────────────────────────────────────────────────────────────
    public function removeFromWhitelist(Request $request)
    {
        $request->validate(['term' => ['required', 'string', 'max:500']]);

        $term     = trim($request->input('term'));
        $unidadId = Auth::check()
            ? optional(app(UnidadActivaService::class)->get(Auth::user()))->id
            : null;

        try {
            $count = EntityWhitelist::whereRaw('LOWER(term) = LOWER(?)', [$term])
                ->when($unidadId, fn($q) => $q->where('unidad_id', $unidadId))
                ->when(!$unidadId, fn($q) => $q->whereNull('unidad_id'))
                ->delete();

            return response()->json([
                'success' => true,
                'message' => $count > 0
                    ? "\"$term\" eliminado de la whitelist."
                    : "\"$term\" no se encontró en la whitelist.",
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar de whitelist: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar de la whitelist.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // removeFromBlacklist  — Elimina un término de la blacklist por nombre
    //
    // Recibe via AJAX (POST JSON):
    //   - term : texto de la entidad a eliminar (búsqueda exacta case-insensitive)
    //
    // Elimina todas las entradas de la unidad activa con ese término.
    // ─────────────────────────────────────────────────────────────────────────
    public function removeFromBlacklist(Request $request)
    {
        $request->validate(['term' => ['required', 'string', 'max:500']]);

        $term     = trim($request->input('term'));
        $unidadId = Auth::check()
            ? optional(app(UnidadActivaService::class)->get(Auth::user()))->id
            : null;

        try {
            $count = EntityBlacklist::whereRaw('LOWER(term) = LOWER(?)', [$term])
                ->when($unidadId, fn($q) => $q->where('unidad_id', $unidadId))
                ->when(!$unidadId, fn($q) => $q->whereNull('unidad_id'))
                ->delete();

            return response()->json([
                'success' => true,
                'message' => $count > 0
                    ? "\"$term\" eliminado de la blacklist."
                    : "\"$term\" no se encontró en la blacklist.",
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar de blacklist: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar de la blacklist.'], 500);
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
                ->orderBy('term', 'asc')
                ->get();
        } catch (\Exception $e) {
            \Log::error('Error al cargar whitelist: ' . $e->getMessage());
            $entries = collect();
        }

        $entityColors = $this->getUserEntityColors();
        return view('whitelist.index', compact('entries', 'entityColors'));
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
            $unidadId = Auth::check()
                ? optional(app(UnidadActivaService::class)->get(Auth::user()))->id
                : null;

            return EntityWhitelist::active()
                ->where(function ($q) use ($unidadId) {
                    if ($unidadId) {
                        $q->where('unidad_id', $unidadId);
                    } else {
                        $q->whereNull('unidad_id');
                    }
                })
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
    //   2a. Si el NLP ya lo detectó con un tipo de MENOR prioridad (ej: MISC),
    //       reemplaza ese span por el tipo de la whitelist (ej: PER) y marca
    //       el span con data-whitelist="1".
    //   2b. Si el NLP lo detectó con igual o mayor prioridad, no hace nada.
    //   3. Si no fue detectado, lo añade al array de entidades Y envuelve sus
    //      ocurrencias en el HTML con el <span class="entity ..."> apropiado,
    //      SOLO en nodos de texto fuera de spans de entidades existentes.
    //
    // Prioridades: PER=1 > DNI/EMAIL/PHONE=2 > ORG/LOC=3 > DATE=4 > MISC=5
    // ─────────────────────────────────────────────────────────────────────────
    private function injectWhitelistEntities(array $entities, string $html, string $originalText): array
    {
        $whitelist = $this->getActiveWhitelist();
        if (empty($whitelist)) {
            return ['entities' => $entities, 'html' => $html];
        }

        // Mapa de prioridad: menor número = mayor prioridad (PER siempre gana sobre MISC)
        $typePriority = [
            'PER' => 1, 'PERSON' => 1,
            'DNI' => 2, 'EMAIL' => 2, 'PHONE' => 2,
            'ORG' => 3, 'LOC' => 3, 'GPE' => 3,
            'DATE' => 4,
            'MISC' => 5,
        ];

        $normOriginal = self::normalizeForMatch($originalText);

        // Índice de entidades existentes por texto normalizado: [norm => [index, ...]]
        $existingByNorm = [];
        foreach ($entities as $i => $ent) {
            $norm = self::normalizeForMatch($ent['text'] ?? '');
            if ($norm !== '') {
                $existingByNorm[$norm][] = $i;
            }
        }

        $toInjectNew = [];  // Términos no detectados por el NLP

        foreach ($whitelist as $entry) {
            $term = trim($entry['term'] ?? '');
            if ($term === '') continue;

            // El término debe aparecer en el texto fuente
            if (mb_strpos($normOriginal, self::normalizeForMatch($term)) === false) continue;

            $entityType  = strtoupper($entry['entity_type'] ?? 'PER') ?: 'PER';
            $wlPriority  = $typePriority[$entityType] ?? 5;
            $normTerm    = self::normalizeForMatch($term);
            $newClass    = $this->entityTypeToClass($entityType);

            if (isset($existingByNorm[$normTerm])) {
                // El NLP lo detectó — verificar si la whitelist tiene mayor prioridad
                foreach ($existingByNorm[$normTerm] as $idx) {
                    $oldLabel    = $entities[$idx]['label'] ?? '';
                    $oldPriority = $typePriority[$oldLabel] ?? 5;
                    $textPat     = self::buildFlexiblePattern($term);

                    if ($wlPriority < $oldPriority) {
                        // La whitelist tiene mayor prioridad: reemplazar tipo en el array
                        $entities[$idx]['label'] = $entityType;

                        // Reemplazar el span en el HTML (tipo y clase) + marcar con data-whitelist
                        $pattern = '/<span\b[^>]*\bdata-label="' . preg_quote($oldLabel, '/') . '"[^>]*>\s*(' . $textPat . ')\s*<\/span>/iu';
                        $html    = preg_replace(
                            $pattern,
                            '<span class="entity ' . $newClass . '" data-label="' . $entityType . '" data-whitelist="1">$1</span>',
                            $html
                        );
                    } else {
                        // El NLP tiene igual o mayor prioridad: mantener tipo pero marcar data-whitelist
                        // para que el tooltip muestre el badge "Whitelist".
                        $pattern = '/<span\b(?![^>]*data-whitelist)([^>]*\bdata-label="' . preg_quote($oldLabel, '/') . '"[^>]*)>\s*(' . $textPat . ')\s*<\/span>/iu';
                        $html    = preg_replace(
                            $pattern,
                            '<span data-whitelist="1"$1>$2</span>',
                            $html
                        );
                    }
                }
                // Siempre programar para inyección en texto plano: puede haber ocurrencias
                // del mismo término en texto plano (ej: segunda mención que el filtro de ruido
                // NLP descartó como MISC). El paso de inyección respeta la profundidad de spans
                // y no inyecta dentro de spans ya existentes.
                $toInjectNew[] = ['term' => $term, 'type' => $entityType, 'class' => $newClass];
            } else {
                // No detectado por el NLP — inyectar como entidad nueva
                $toInjectNew[] = ['term' => $term, 'type' => $entityType, 'class' => $newClass];
                $entities[]    = ['text' => $term, 'label' => $entityType, 'start' => null, 'end' => null];
                $existingByNorm[$normTerm] = [count($entities) - 1];
            }
        }

        // ── Paso 2: Strip entity spans that overlap with a whitelist term ────────────────
        // Handles two cases:
        //   A) The span text CONTAINS the whitelist term as a substring
        //      e.g. NLP span "Juan Carlos García" contains whitelist "Juan Carlos"
        //   B) The whitelist term CROSSES a span boundary
        //      e.g. text has "FRANCO, " before the span and the span contains
        //      "HÉCTOR S/ ABUSO SEXUAL" — whitelist term is "FRANCO, HÉCTOR".
        //      The NLP span absorbs "HÉCTOR", splitting the whitelist term.
        // In both cases the span is stripped so the injection pass can re-highlight
        // exactly the whitelist term. Exact-match spans are left untouched.
        $strippedNorms = [];
        foreach ($toInjectNew as $inj) {
            $textPat  = self::buildFlexiblePattern($inj['term']);
            $normTerm = self::normalizeForMatch($inj['term']);

            // The regex captures: (text before span)(span inner text)
            // [^<]* matches the text node immediately preceding the span tag.
            $html = preg_replace_callback(
                '/([^<]*)<span\b([^>]*class="entity[^"]*"[^>]*)>([^<]+)<\/span>/iu',
                function ($m) use ($textPat, $normTerm, &$strippedNorms) {
                    $before    = $m[1]; // text node immediately before the span
                    $inner     = $m[3]; // text inside the span
                    $normInner = self::normalizeForMatch($inner);

                    // Case A: whitelist term fully inside the span (not exact match)
                    if ($normInner !== $normTerm && preg_match('/' . $textPat . '/iu', $inner)) {
                        $strippedNorms[] = $normInner;
                        return $before . $inner; // strip span wrapper
                    }

                    // Case B: whitelist term crosses the span boundary
                    // (part in $before text node, part inside the span)
                    $combined = $before . $inner;
                    if (
                        preg_match('/' . $textPat . '/iu', $combined) &&
                        !preg_match('/' . $textPat . '/iu', $inner) &&
                        !preg_match('/' . $textPat . '/iu', $before)
                    ) {
                        $strippedNorms[] = $normInner;
                        return $before . $inner; // strip span, reunite text
                    }

                    return $m[0]; // unchanged
                },
                $html
            );
        }

        // Remove entities whose spans were stripped (whitelist entity replaces them)
        if (!empty($strippedNorms)) {
            $entities = array_values(array_filter(
                $entities,
                fn($e) => !in_array(self::normalizeForMatch($e['text'] ?? ''), $strippedNorms, true)
            ));
        }

        if (empty($toInjectNew)) {
            return ['entities' => array_values($entities), 'html' => $html];
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
                    foreach ($toInjectNew as $inj) {
                        $part = preg_replace_callback(
                            '/(' . self::buildFlexiblePattern($inj['term']) . ')/iu',
                            fn($m) => '<span class="entity ' . $inj['class'] . '" data-label="' . $inj['type'] . '" data-whitelist="1">' . $m[0] . '</span>',
                            $part
                        );
                    }
                }
                $result[] = $part;
            }
        }

        $finalHtml = implode('', $result);

        // Rebuild entities from the painted HTML spans so the panel count
        // reflects every occurrence including whitelist-injected variants.
        $entities = [];
        preg_match_all(
            '/<span\b[^>]*\bclass="entity[^"]*"[^>]*\bdata-label="([^"]+)"[^>]*>([^<]*)<\/span>/iu',
            $finalHtml,
            $htmlMatches,
            PREG_SET_ORDER
        );
        foreach ($htmlMatches as $hm) {
            $spanText = html_entity_decode($hm[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (trim($spanText) === '') {
                continue;
            }
            $entities[] = [
                'text'  => trim($spanText),
                'label' => $hm[1],
                'start' => null,
                'end'   => null,
            ];
        }

        return ['entities' => $entities, 'html' => $finalHtml];
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
                ->orderBy('term', 'asc')
                ->get();
        } catch (\Exception $e) {
            \Log::error('Error al cargar blacklist: ' . $e->getMessage());
            $entries = collect();
        }

        $entityColors = $this->getUserEntityColors();
        return view('blacklist.index', compact('entries', 'entityColors'));
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
