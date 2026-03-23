<?php
/**
 * Test del circuito whitelist: verifica que el término guardado en la
 * whitelist aparece como entidad detectada al analizar actuacion.pdf.
 */

$baseUrl = 'http://localhost:8080';
$pdfPath = '/var/www/actuacion.pdf';

// 1. Obtener CSRF
$ch = curl_init($baseUrl . '/pdf-analyzer');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR      => '/tmp/jar_wl.txt',
    CURLOPT_COOKIEFILE     => '/tmp/jar_wl.txt',
    CURLOPT_FOLLOWLOCATION => true,
]);
$html = curl_exec($ch);
curl_close($ch);

preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m);
$token = $m[1] ?? '';
if (!$token) { echo "ERROR: sin CSRF token\n"; exit(1); }

// 2. Subir PDF
$ch2 = curl_init($baseUrl . '/pdf-analyzer/process');
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_COOKIEJAR      => '/tmp/jar_wl.txt',
    CURLOPT_COOKIEFILE     => '/tmp/jar_wl.txt',
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

echo "HTTP $code\n";
if ($code !== 200) { echo "ERROR\n"; exit(1); }

// 3. Verificar entidades generales
preg_match_all('/class="entity-row"/', $resp, $rows);
echo "Total entidades: " . count($rows[0]) . "\n";

// 4. Buscar entidades de la whitelist en la respuesta
require '/var/www/vendor/autoload.php';
$app = require '/var/www/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$whitelistTerms = \App\Models\EntityWhitelist::active()->pluck('term')->toArray();

echo "\nTérminos en whitelist: " . implode(', ', $whitelistTerms) . "\n";
echo "Verificando presencia en la respuesta:\n";

foreach ($whitelistTerms as $term) {
    // Verificar si aparece como span de entidad
    $inSpan    = (bool) preg_match('/<span[^>]*class="entity[^"]*"[^>]*>' . preg_quote($term, '/') . '<\/span>/i', $resp);
    // Verificar si aparece sin marcar (texto plano)
    $plainText = (bool) strpos($resp, $term);
    
    if ($inSpan) {
        echo "  ✓ \"$term\" detectado COMO ENTIDAD (span presente)\n";
    } elseif ($plainText) {
        echo "  ✗ \"$term\" aparece en el texto PERO NO como entidad — revisar injectWhitelistEntities\n";
    } else {
        echo "  - \"$term\" NO aparece en el texto del PDF\n";
    }
}
