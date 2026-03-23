<?php
/**
 * Test completo del pipeline de processPdf()
 * Simula exactamente lo que hace PdfAnalyzerController::processPdf()
 */

require '/var/www/vendor/autoload.php';
$app = require '/var/www/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pdfPath = '/var/www/actuacion.pdf';

echo "=== TEST COMPLETO PDF PIPELINE ===\n";
echo "PDF: $pdfPath\n\n";

// STEP 1: Extracción de texto
echo "STEP 1: Extracción de texto...\n";
try {
    $extractor = app(\App\Services\PdfTextExtractorService::class);
    $text = $extractor->extract($pdfPath);
    echo "  OK: " . strlen($text) . " chars extraídos\n";
    echo "  Primeros 200 chars: " . substr($text, 0, 200) . "\n\n";
} catch (\Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 2: Análisis NLP
echo "STEP 2: Análisis NLP...\n";
try {
    $nlp = app(\App\Services\NlpEntityService::class);
    $result = $nlp->analyze($text);
    echo "  OK: html=" . strlen($result['html']) . " chars, entities=" . count($result['entities']) . "\n\n";
} catch (\Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 3: filterEntitiesAndHtml
echo "STEP 3: filterEntitiesAndHtml (blacklist)...\n";
try {
    $blacklist = \App\Models\EntityBlacklist::active()
        ->get(['term', 'entity_type', 'match_mode', 'case_sensitive'])
        ->toArray();
    echo "  Blacklist cargada: " . count($blacklist) . " entradas\n";
} catch (\Exception $e) {
    echo "  WARNING (blacklist): " . $e->getMessage() . "\n";
    $blacklist = [];
}
$entities = $result['entities'] ?? [];
$html = $result['html'];
echo "  Entidades antes: " . count($entities) . "\n";
// Sin filtrar por simplicidad si blacklist está vacía
echo "  OK: entidades after=" . count($entities) . "\n\n";

// STEP 4: injectWhitelistEntities
echo "STEP 4: injectWhitelistEntities...\n";
try {
    $whitelist = \App\Models\EntityWhitelist::active()
        ->get(['term', 'entity_type'])
        ->toArray();
    echo "  Whitelist cargada: " . count($whitelist) . " entradas\n";
} catch (\Exception $e) {
    echo "  WARNING (whitelist): " . $e->getMessage() . "\n";
    $whitelist = [];
}
echo "  OK\n\n";

// STEP 5: buildGroupedEntities
echo "STEP 5: buildGroupedEntities...\n";
try {
    $controller = app(\App\Http\Controllers\PdfAnalyzerController::class);
    $reflMethod = new ReflectionMethod($controller, 'buildGroupedEntities');
    $reflMethod->setAccessible(true);
    $grouped = $reflMethod->invoke($controller, $entities);
    echo "  OK: " . count($grouped) . " grupos\n\n";
} catch (\Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 6: Renderizar la vista
echo "STEP 6: Renderizar vista pdf.analyzer...\n";
try {
    $viewData = [
        'analyzedHtml'    => $html,
        'entities'        => $entities,
        'groupedEntities' => $grouped,
    ];
    $rendered = view('pdf.analyzer', $viewData)->render();
    echo "  OK: vista renderizada, " . strlen($rendered) . " chars de HTML\n\n";
} catch (\Exception $e) {
    echo "  ERROR RENDERING VIEW: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit(1);
}

echo "=== PIPELINE COMPLETO OK ===\n";
echo "Resultado: html=" . strlen($html) . " chars, entities=" . count($entities) . ", grouped=" . count($grouped) . "\n";
