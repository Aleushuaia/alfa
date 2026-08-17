<?php
require '/var/www/vendor/autoload.php';

$svc = new App\Services\PdfCompressionService();

$inputPath  = '/var/www/imgprueba.pdf';
$outputPath = '/var/www/imgprueba_comprimido.pdf';

$origSize = filesize($inputPath);

$svc->compress($inputPath, $outputPath, 'ebook');

$compSize  = filesize($outputPath);
$reduction = round((1 - $compSize / $origSize) * 100, 1);

echo "Original:   " . number_format($origSize)  . " bytes (" . round($origSize/1024)  . " KB)\n";
echo "Comprimido: " . number_format($compSize)   . " bytes (" . round($compSize/1024)  . " KB)\n";
echo "Reduccion:  {$reduction}%\n";
echo "OK - guardado en imgprueba_comprimido.pdf\n";
