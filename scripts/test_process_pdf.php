<?php
/**
 * test_process_pdf.php
 * Simula el flujo completo de PdfAnalyzerController::processPdf()
 * con el archivo actuacion.pdf para detectar el error exacto.
 */

chdir('/var/www');
define('LARAVEL_START', microtime(true));

require '/var/www/vendor/autoload.php';

$app = require_once '/var/www/bootstrap/app.php';

// Arrancar el kernel de consola para inicializar el contenedor
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TEST: processPdf con actuacion.pdf ===" . PHP_EOL . PHP_EOL;

$pdfPath = '/var/www/actuacion.pdf';

// ──────────────────────────────────────────────────────────────────────────
// 1. Extraer texto
// ──────────────────────────────────────────────────────────────────────────
echo "1) PdfTextExtractorService::extract() ...\n";
try {
    $extractor = $app->make(\App\Services\PdfTextExtractorService::class);
    $text = $extractor->extract($pdfPath);
    echo "   OK — " . mb_strlen($text) . " caracteres extraídos.\n";
    echo "   Primeros 200 chars: " . mb_substr($text, 0, 200) . "\n\n";
} catch (\Throwable $e) {
    echo "   ERROR en extractor: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "   En: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Stack:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// ──────────────────────────────────────────────────────────────────────────
// 2. Analizar con NLP
// ──────────────────────────────────────────────────────────────────────────
echo "2) NlpEntityService::analyze() ...\n";
try {
    $nlp    = $app->make(\App\Services\NlpEntityService::class);
    $result = $nlp->analyze($text);
    echo "   OK — " . count($result['entities'] ?? []) . " entidades, HTML length: " . mb_strlen($result['html'] ?? '') . "\n\n";
} catch (\Throwable $e) {
    echo "   ERROR en NLP: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "   En: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

// ──────────────────────────────────────────────────────────────────────────
// 3. Cargar blacklist desde PostgreSQL
// ──────────────────────────────────────────────────────────────────────────
echo "3) EntityBlacklist::active()->get() ...\n";
try {
    $blacklist = \App\Models\EntityBlacklist::active()
        ->get(['term', 'entity_type', 'match_mode', 'case_sensitive'])
        ->toArray();
    echo "   OK — " . count($blacklist) . " entradas en la blacklist.\n\n";
} catch (\Throwable $e) {
    echo "   ERROR al cargar blacklist: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "   En: " . $e->getFile() . ":" . $e->getLine() . "\n";
    // No es fatal, continuamos
}

// ──────────────────────────────────────────────────────────────────────────
// 4. Intentar renderizar la vista
// ──────────────────────────────────────────────────────────────────────────
echo "4) Renderizando vista pdf.analyzer ...\n";
try {
    $view = view('pdf.analyzer', [
        'analyzedHtml'    => $result['html'] ?? '',
        'entities'        => $result['entities'] ?? [],
        'groupedEntities' => [],
    ]);
    $rendered = $view->render();
    echo "   OK — Vista renderizada: " . mb_strlen($rendered) . " bytes.\n\n";
} catch (\Throwable $e) {
    echo "   ERROR al renderizar vista: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "   En: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Stack:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "=== TODO OK ✓ ===" . PHP_EOL;
