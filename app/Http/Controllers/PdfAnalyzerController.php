<?php

namespace App\Http\Controllers;

use App\Services\NlpEntityService;
use App\Services\PdfTextExtractorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
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
        return view('pdf.analyzer');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // processPdf  — Recibe el PDF, extrae texto y llama al microservicio NLP
    // ─────────────────────────────────────────────────────────────────────────
    public function processPdf(Request $request)
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'], // 20 MB
        ]);

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

        // 4. Guardar el HTML etiquetado en sesión para reutilizarlo en anonimización/exportación
        session(['pdf_analyzed_html' => $result['html']]);

        // Prepare grouped entities: perform normalization to detect duplicates/variants
        $entities = $result['entities'] ?? [];
        $grouped = [];

        // helper to normalize text for grouping
        $normalize = function (string $s) {
            $s = mb_strtolower(trim($s));
            // remove accents
            $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
            // remove punctuation except spaces and alnum
            $s = preg_replace('/[^a-z0-9\s]/i', ' ', $s);
            // collapse whitespace
            $s = preg_replace('/\s+/', ' ', $s);
            return trim($s);
        };

        // Map normalized key => grouped entry index key
        $normMap = [];
        foreach ($entities as $ent) {
            $text  = trim($ent['text'] ?? '');
            $label = $ent['label'] ?? '';
            if ($text === '') continue;

            $norm = $normalize($text) . '||' . $label;

            if (!isset($normMap[$norm])) {
                // create new grouped item
                $grouped[] = [
                    'text'      => $text,             // representative display text (first seen)
                    'label'     => $label,
                    'count'     => 0,
                    'positions' => [],
                    'variants'  => [],               // list of visible variant texts
                    'variant_counts' => [],
                ];
                $normMap[$norm] = count($grouped) - 1;
            }

            $idx = $normMap[$norm];
            $grouped[$idx]['count']++;
            $grouped[$idx]['positions'][] = [
                'start' => $ent['start'] ?? null,
                'end'   => $ent['end'] ?? null,
            ];

            // track variants and their counts
            if (!in_array($text, $grouped[$idx]['variants'], true)) {
                $grouped[$idx]['variants'][] = $text;
                $grouped[$idx]['variant_counts'][$text] = 0;
            }
            $grouped[$idx]['variant_counts'][$text]++;
            // choose a better representative text: the variant with highest count or longer length
            $best = $grouped[$idx]['text'];
            $bestCount = $grouped[$idx]['variant_counts'][$best] ?? 0;
            $thisCount = $grouped[$idx]['variant_counts'][$text];
            if ($thisCount > $bestCount || ( $thisCount === $bestCount && mb_strlen($text) > mb_strlen($best) )) {
                $grouped[$idx]['text'] = $text;
            }
        }

        // cleanup: remove internal variant_counts before returning
        foreach ($grouped as &$g) {
            unset($g['variant_counts']);
        }

        return view('pdf.analyzer', [
            'analyzedHtml'    => $result['html'],
            'entities'        => $entities,
            'groupedEntities' => array_values($grouped),
        ]);
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
}
