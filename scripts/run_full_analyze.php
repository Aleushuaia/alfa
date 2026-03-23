<?php
// Simula el flujo del controlador: extraer texto + llamar al microservicio NLP
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\PdfTextExtractorService;
use App\Services\NlpEntityService;

$pdfPath = '/var/www/actuacion.pdf';
$extractor = new PdfTextExtractorService();
$nlp = new NlpEntityService();

try {
    echo "1) Extrayendo texto...\n";
    $text = $extractor->extract($pdfPath);
    echo "Texto extraído (primeros 300 chars):\n" . substr($text,0,300) . "\n\n";

    echo "2) Llamando al microservicio NLP...\n";
    $result = $nlp->analyze($text);

    echo "NLP OK. HTML length: " . strlen($result['html']) . "\n";
    echo "Entidades detectadas: " . count($result['entities']) . "\n";
    echo "Primeras 5 entidades:\n";
    foreach (array_slice($result['entities'],0,5) as $e) {
        echo " - [" . ($e['label'] ?? '') . "] '" . ($e['text'] ?? '') . "' (" . ($e['start'] ?? '') . "," . ($e['end'] ?? '') . ")\n";
    }

} catch (Exception $ex) {
    echo "ERROR en flujo: " . $ex->getMessage() . "\n";
    echo $ex->getTraceAsString();
}
