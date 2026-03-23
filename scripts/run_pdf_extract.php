<?php
// Script para probar extracción de texto de un PDF concreto
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\PdfTextExtractorService;

$svc = new PdfTextExtractorService();
$path = '/var/www/actuacion.pdf';
try {
    $text = $svc->extract($path);
    echo "EXTRACTION OK\n";
    echo substr($text,0,1000) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
