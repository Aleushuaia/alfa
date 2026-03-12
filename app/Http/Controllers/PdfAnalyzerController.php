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

        // Prepare grouped entities: group by text + label and count occurrences
        $entities = $result['entities'] ?? [];
        $grouped = [];
        foreach ($entities as $ent) {
            $text  = trim($ent['text'] ?? '');
            $label = $ent['label'] ?? '';
            if ($text === '') continue;
            $key = $text . '||' . $label;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'text'      => $text,
                    'label'     => $label,
                    'count'     => 0,
                    'positions' => [],
                ];
            }
            $grouped[$key]['count']++;
            $grouped[$key]['positions'][] = [
                'start' => $ent['start'] ?? null,
                'end'   => $ent['end'] ?? null,
            ];
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
