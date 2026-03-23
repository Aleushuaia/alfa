#!/usr/bin/env php
<?php
/**
 * Simula POST a /pdf-analyzer/analyze-text con texto de prueba
 */

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
if ($code !== 200) { echo "ERROR: no se pudo cargar el formulario.\n"; exit(1); }

if (!preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m)) {
    echo "ERROR: no se pudo extraer el CSRF token.\n"; exit(1);
}
$token = $m[1];

$text = "Este es un texto de prueba que menciona a Juan Pérez y la organización Acme S.A. para verificar el análisis de entidades.";

$ch = curl_init('http://localhost:8080/pdf-analyzer/analyze-text');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_COOKIEJAR      => '/tmp/jar.txt',
    CURLOPT_COOKIEFILE     => '/tmp/jar.txt',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_POSTFIELDS     => [
        '_token' => $token,
        'text'   => $text,
    ],
    CURLOPT_TIMEOUT        => 60,
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

echo "POST /pdf-analyzer/analyze-text → HTTP $code\n";
if ($err) { echo "CURL error: $err\n"; exit(1); }

if ($code === 200) {
    if (strpos($body, 'alert-danger') !== false) {
        preg_match('/<div[^>]*alert-danger[^>]*>.*?<\/div>/si', $body, $m2);
        echo "ALERTA EN LA VISTA: " . strip_tags($m2[0] ?? 'no se pudo extraer') . "\n";
    }
    if (strpos($body, 'entity-row') !== false) {
        preg_match_all('/entity-row/', $body, $rows);
        echo "OK — Se encontraron " . count($rows[0]) . " filas de entidades en la respuesta." . "\n";
    } else {
        echo "ADVERTENCIA: No se detectaron filas de entidades en la respuesta.\n";
    }
    file_put_contents('/tmp/analyzer_text_response.html', $body);
    echo "Respuesta guardada en /tmp/analyzer_text_response.html (" . strlen($body) . " bytes)\n";
} else {
    echo "ERROR HTTP $code\n";
    file_put_contents('/tmp/analyzer_text_response.html', $body ?: '');
    echo "Respuesta guardada en /tmp/analyzer_text_response.html\n";
}
