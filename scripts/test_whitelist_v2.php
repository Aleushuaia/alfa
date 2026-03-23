<?php
/**
 * Test whitelist: verifica que "declaratoria" se inyecta como entidad
 * usando exactamente la misma estructura del test_http_process.php que funciona.
 */

$ch = curl_init('http://localhost:8080/pdf-analyzer');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR      => '/tmp/jar_wltest.txt',
    CURLOPT_COOKIEFILE     => '/tmp/jar_wltest.txt',
    CURLOPT_FOLLOWLOCATION => true,
]);
$html = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "GET → HTTP $code\n";

if (!preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m)) {
    echo "ERROR: sin CSRF\n"; exit(1);
}
$token = $m[1];
echo "Token: " . substr($token, 0, 20) . "...\n";

$pdfPath = '/var/www/actuacion.pdf';
$ch2 = curl_init('http://localhost:8080/pdf-analyzer/process');
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_COOKIEJAR      => '/tmp/jar_wltest.txt',
    CURLOPT_COOKIEFILE     => '/tmp/jar_wltest.txt',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 180,
    CURLOPT_POSTFIELDS     => [
        '_token' => $token,
        'pdf'    => new CURLFile($pdfPath, 'application/pdf', 'actuacion.pdf'),
    ],
]);
$resp = curl_exec($ch2);
$code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);
echo "POST → HTTP $code\n";
if ($code !== 200) { echo substr($resp, 0, 500); exit(1); }

preg_match_all('/class="entity-row"/', $resp, $rows);
echo "Total entidades: " . count($rows[0]) . "\n";

// Verificar el término "declaratoria" de la whitelist
$term = 'declaratoria';
$inSpan = (bool) preg_match('/<span[^>]*class="entity[^"]*"[^>]*[^>]*>' . preg_quote($term, '/') . '<\/span>/i', $resp);
$exists = (stripos($resp, $term) !== false);

if ($inSpan) {
    echo "\n✓ \"$term\" detectado COMO ENTIDAD (whitelist funciona correctamente)\n";
} elseif ($exists) {
    echo "\n⚠ \"$term\" aparece en el texto pero NO como entidad (whitelist no inyecta)\n";
    // Mostrar el contexto donde aparece
    $pos = stripos($resp, $term);
    echo "  Contexto: ..." . htmlspecialchars(substr($resp, max(0, $pos-80), 200)) . "...\n";
} else {
    echo "\n- \"$term\" NO aparece en el texto del documento\n";
}
