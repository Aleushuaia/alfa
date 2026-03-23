<?php
/**
 * test_storage.php - Diagnostica el almacenamiento de un archivo subido
 */

// Simular el comportamiento del controlador sin HTTP:
// Tomar el PDF directamente del filesystem y almacenarlo

$pdfSrc = '/tmp/prueba.pdf';
$storageBase = '/var/www/storage/app';
$storeDir = $storageBase . '/temp-ocr';

echo "=== Diagnóstico de almacenamiento ===\n";
echo "Fuente PDF: $pdfSrc - " . (file_exists($pdfSrc) ? 'OK (' . filesize($pdfSrc) . ' bytes)' : 'NO EXISTE') . "\n";
echo "Directorio temp-ocr: $storeDir - " . (is_dir($storeDir) ? 'OK' : 'NO EXISTE') . "\n";
echo "Writable temp-ocr: " . (is_writable($storeDir) ? 'SI' : 'NO') . "\n";

// Simular el store
$uuid = str_replace('-', '', sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
));
$destPath = $storeDir . '/' . $uuid . '.pdf';

if (copy($pdfSrc, $destPath)) {
    echo "Archivo copiado a: $destPath\n";
    echo "Readable: " . (is_readable($destPath) ? 'SI' : 'NO') . "\n";
    echo "Tamaño: " . filesize($destPath) . " bytes\n";

    // Probar pdftoppm directamente
    $tmpDir = '/tmp/ocr_direct_' . $uuid;
    mkdir($tmpDir, 0700, true);
    $cmd = sprintf('pdftoppm -r 200 -png %s %s 2>&1', escapeshellarg($destPath), escapeshellarg($tmpDir . '/page'));
    exec($cmd, $out, $code);
    echo "pdftoppm exit: $code\n";
    echo "pdftoppm output: " . implode(' | ', $out) . "\n";
    $imgs = glob($tmpDir . '/page*.png');
    echo "Imágenes generadas: " . count($imgs) . "\n";

    if (!empty($imgs)) {
        $outBase = $tmpDir . '/result';
        $cmd2 = sprintf('tesseract %s %s -l spa --psm 3 --oem 1 2>&1', escapeshellarg($imgs[0]), escapeshellarg($outBase));
        exec($cmd2, $out2, $code2);
        echo "Tesseract exit: $code2\n";
        if (file_exists($outBase . '.txt')) {
            echo "Texto extraído:\n";
            echo substr(file_get_contents($outBase . '.txt'), 0, 800);
        }
        array_map('unlink', glob($tmpDir . '/*'));
        rmdir($tmpDir);
    }

    unlink($destPath);
} else {
    echo "ERROR: No se pudo copiar el archivo a temp-ocr\n";
}
