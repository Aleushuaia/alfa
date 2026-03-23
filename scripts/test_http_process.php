#!/usr/bin/env php
<?php
/**
 * test_http_process.php
 * Simula el POST a /pdf-analyzer/process con actuacion.pdf
 * usando curl directo al servidor nginx interno.
 */

// 1. Obtener cookies + CSRF token de la página GET
$ch = curl_init('http://localhost:8080/pdf-analyzer');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR      => '/tmp/jar.txt',
    CURLOPT_COOKIEFILE     => '/tmp/jar.txt',
    CURLOPT_FOLLOWLOCATION => true,
]);
$html = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "GET /pdf-analyzer → HTTP $code\n";
if ($code !== 200) {
    echo "ERROR: no se pudo cargar el formulario.\n";
    exit(1);
}

// Extraer _token del HTML
if (!preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m)) {
    echo "ERROR: no se pudo extraer el CSRF token.\n";
    exit(1);
}
$token = $m[1];
echo "CSRF token obtenido: " . substr($token, 0, 20) . "...\n";

// 2. POST con el PDF
$pdfPath = '/var/www/actuacion.pdf';
if (!file_exists($pdfPath)) {
    echo "ERROR: $pdfPath no existe.\n";
    exit(1);
}

$ch = curl_init('http://localhost:8080/pdf-analyzer/process');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_COOKIEJAR      => '/tmp/jar.txt',
    CURLOPT_COOKIEFILE     => '/tmp/jar.txt',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_POSTFIELDS     => [
        '_token' => $token,
        'pdf'    => new CURLFile($pdfPath, 'application/pdf', 'actuacion.pdf'),
    ],
    CURLOPT_TIMEOUT        => 120,
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

echo "POST /pdf-analyzer/process → HTTP $code\n";

if ($err) {
    echo "CURL error: $err\n";
    exit(1);
}

if ($code === 200) {
    // Buscar si hay errores en el HTML de respuesta
    if (strpos($body, 'alert-danger') !== false) {
        preg_match('/<div[^>]*alert-danger[^>]*>.*?<\/div>/si', $body, $m2);
        echo "ALERTA EN LA VISTA: " . strip_tags($m2[0] ?? 'no se pudo extraer') . "\n";
    }
    // Verificar que llegaron entidades
    if (strpos($body, 'entity-row') !== false) {
        preg_match_all('/entity-row/', $body, $rows);
        echo "OK — Se encontraron " . count($rows[0]) . " filas de entidades en la respuesta.\n";
    } else {
        echo "ADVERTENCIA: No se detectaron filas de entidades en la respuesta.\n";
    }
    // Guardar respuesta para inspección
    file_put_contents('/tmp/analyzer_response.html', $body);
    echo "Respuesta guardada en /tmp/analyzer_response.html (" . strlen($body) . " bytes)\n";
} else {
    echo "ERROR HTTP $code\n";
    // Intentar extraer mensaje de error
    if (strpos($body, 'Whoops') !== false || strpos($body, 'Exception') !== false) {
        preg_match('/<title>(.*?)<\/title>/si', $body, $t);
        echo "Título: " . ($t[1] ?? '?') . "\n";
        preg_match('/<div[^>]*class="[^"]*exception[^"]*"[^>]*>(.*?)<\/div>/si', $body, $ex);
        echo "Excepción (primeros 500 chars):\n" . substr(strip_tags($ex[1] ?? ''), 0, 500) . "\n";
    }
    file_put_contents('/tmp/analyzer_response.html', $body);
    echo "Respuesta completa guardada en /tmp/analyzer_response.html\n";
}
