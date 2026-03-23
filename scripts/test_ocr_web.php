<?php
/**
 * test_ocr_web.php
 * Prueba completa del endpoint OCR via HTTP:
 * 1. GET /ocr-extractor para obtener cookie de sesión y CSRF token
 * 2. POST /ocr-extractor/extract con el PDF y las credenciales CSRF
 */

$baseUrl  = 'http://127.0.0.1:8080';
$pdfPath  = '/tmp/prueba.pdf';
$cookieJar= '/tmp/ocr_test_cookies.txt';

if (!file_exists($pdfPath)) {
    echo "ERROR: No existe el PDF en $pdfPath\n";
    exit(1);
}

echo "=== PASO 1: GET para obtener cookie + CSRF token ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => "$baseUrl/ocr-extractor",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true,
    CURLOPT_COOKIEJAR      => $cookieJar,
    CURLOPT_COOKIEFILE     => $cookieJar,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 30,
]);
$resp1 = curl_exec($ch);
$code1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP GET: $code1\n";

// Extraer CSRF token del meta tag
if (!preg_match('/name="csrf-token"\s+content="([^"]+)"/', $resp1, $m)) {
    // Probar otro orden de atributos
    preg_match('/content="([^"]+)"\s+name="csrf-token"/', $resp1, $m);
}
$token = $m[1] ?? null;
if (!$token) {
    echo "ERROR: No se encontró el CSRF token en la respuesta.\n";
    echo "Primeros 1000 chars de la respuesta:\n";
    echo substr($resp1, 0, 1000);
    exit(1);
}
echo "CSRF Token: $token\n";

echo "\n=== PASO 2: POST del PDF al endpoint OCR ===\n";

$ch2 = curl_init();
curl_setopt_array($ch2, [
    CURLOPT_URL            => "$baseUrl/ocr-extractor/extract",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_COOKIEJAR      => $cookieJar,
    CURLOPT_COOKIEFILE     => $cookieJar,
    CURLOPT_HTTPHEADER     => [
        "X-CSRF-TOKEN: $token",
        "Accept: application/json",
    ],
    CURLOPT_POSTFIELDS     => [
        'pdf' => new CURLFile($pdfPath, 'application/pdf', 'prueba.pdf'),
    ],
    CURLOPT_TIMEOUT        => 300,
]);
$resp2 = curl_exec($ch2);
$code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch2);
curl_close($ch2);

echo "HTTP POST: $code2\n";

if ($curlErr) {
    echo "CURL Error: $curlErr\n";
    exit(1);
}

$data = json_decode($resp2, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "ERROR: Respuesta NO es JSON válido.\n";
    echo "Primeros 2000 chars de la respuesta:\n";
    echo substr($resp2, 0, 2000);
    exit(1);
}

if (isset($data['error'])) {
    echo "ERROR del servidor: " . $data['error'] . "\n";
    exit(1);
}

echo "\n=== RESULTADO ===\n";
echo "Páginas:    " . ($data['pages'] ?? '?') . "\n";
echo "Caracteres: " . ($data['chars'] ?? '?') . "\n";
echo "\n--- TEXTO EXTRAÍDO (primeros 1500 chars) ---\n";
echo substr($data['text'] ?? '', 0, 1500);
echo "\n--- FIN ---\n";
