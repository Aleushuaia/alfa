<?php
// Script de diagnóstico OCR - ejecutar: php scripts/test_ocr_diag.php

echo "=== 1. exec() disponible? ===\n";
if (function_exists('exec')) {
    echo "SI\n";
} else {
    echo "NO - exec() está deshabilitado\n";
    exit(1);
}

echo "\n=== 2. which tesseract ===\n";
exec('which tesseract 2>&1', $out, $code);
echo "exit: $code\n" . implode("\n", $out) . "\n";

echo "\n=== 3. which pdftoppm ===\n";
exec('which pdftoppm 2>&1', $out2, $code2);
echo "exit: $code2\n" . implode("\n", $out2) . "\n";

echo "\n=== 4. tesseract --list-langs ===\n";
exec('tesseract --list-langs 2>&1', $langs, $lcode);
echo "exit: $lcode\n" . implode("\n", $langs) . "\n";

echo "\n=== 5. Crear PDF de prueba con texto ===\n";
// Crear un PDF mínimo con texto usando HTML → si no hay wkhtmltopdf, usamos un PDF hardcoded
$pdfBase64 = 'JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA2MTIgNzkyXQovQ29udGVudHMgNCAwIFIgL1Jlc291cmNlcyA8PCAvRm9udCA8PCAvRjEgNSAwIFIgPj4gPj4gPj4KZW5kb2JqCjQgMCBvYmoKPDwgL0xlbmd0aCA4MyA+PgpzdHJlYW0KQlQKL0YxIDI0IFRmCjEwMCA3MDAgVGQKKEhvbGEgTXVuZG8gLSBQcnVlYmEgT0NSIC0gVGV4dG8gZGUgcHJ1ZWJhIDEyMzQ1NikgVGoKRVQKZW5kc3RyZWFtCmVuZG9iago1IDAgb2JqCjw8IC9UeXBlIC9Gb250IC9TdWJ0eXBlIC9UeXBlMSAvQmFzZUZvbnQgL0hlbHZldGljYSA+PgplbmRvYmoKeHJlZgowIDYKMDAwMDAwMDAwMCA2NTUzNSBmIAowMDAwMDAwMDA5IDAwMDAwIG4gCjAwMDAwMDAwNTggMDAwMDAgbiAKMDAwMDAwMDExNSAwMDAwMCBuIAowMDAwMDAwMjY5IDAwMDAwIG4gCjAwMDAwMDA0MDQgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA2IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgo0ODQKJSVFT0YK';
$pdfPath = '/tmp/test_diag.pdf';
file_put_contents($pdfPath, base64_decode($pdfBase64));
echo "PDF creado en: $pdfPath\n";

echo "\n=== 6. pdftoppm: PDF → PNG ===\n";
$tmpDir = '/tmp/ocr_diag_' . uniqid();
mkdir($tmpDir, 0700, true);
$imgBase = $tmpDir . '/page';
exec("pdftoppm -r 150 -png " . escapeshellarg($pdfPath) . " " . escapeshellarg($imgBase) . " 2>&1", $ppmOut, $ppmCode);
echo "exit: $ppmCode\n" . implode("\n", $ppmOut) . "\n";

$images = glob($tmpDir . '/page*.png');
echo "Imágenes generadas: " . count($images) . "\n";
foreach ($images as $img) {
    echo "  - $img (" . filesize($img) . " bytes)\n";
}

if (!empty($images)) {
    echo "\n=== 7. Tesseract OCR sobre primera imagen (spa) ===\n";
    $outBase = $tmpDir . '/output';
    exec("tesseract " . escapeshellarg($images[0]) . " " . escapeshellarg($outBase) . " -l spa 2>&1", $tOut, $tCode);
    echo "exit: $tCode\n" . implode("\n", $tOut) . "\n";

    $txtFile = $outBase . '.txt';
    if (file_exists($txtFile)) {
        echo "Texto extraído:\n" . file_get_contents($txtFile) . "\n";
    } else {
        echo "Archivo .txt no generado\n";
    }
}

// Cleanup
exec("rm -rf " . escapeshellarg($tmpDir));
unlink($pdfPath);
echo "\n=== FIN DIAGNÓSTICO ===\n";
