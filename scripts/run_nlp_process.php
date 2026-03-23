<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\PdfTextExtractorService;
use App\Services\NlpEntityService;

$pdf = '/var/www/actuacion.pdf';
echo "Processing: $pdf\n";
try {
    $extractor = new PdfTextExtractorService();
    $text = $extractor->extract($pdf);
    echo "Extracted chars: " . mb_strlen($text) . "\n";

    $nlp = new NlpEntityService();
    $res = $nlp->analyze($text);

    echo "NLP returned: html length=" . strlen($res['html']) . ", entities=" . count($res['entities']) . "\n";
    // Print first entity sample
    if (!empty($res['entities'])) {
        echo "First entity: " . json_encode($res['entities'][0]) . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
