<?php
require '/var/www/vendor/autoload.php';

$svc  = new App\Services\OcrExtractorService();
$text = $svc->extractFromPdf('/var/www/imgprueba.pdf');

file_put_contents('/var/www/imgprueba_ocr.txt', $text);

echo 'chars='  . strlen($text) . "\n";
echo 'lines='  . (substr_count($text, "\n") + 1) . "\n";
echo "OK - guardado en imgprueba_ocr.txt\n";
