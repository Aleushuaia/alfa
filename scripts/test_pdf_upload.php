<?php
/**
 * Test funcional: subir actuacion.pdf al endpoint /pdf-analyzer/process
 * y verificar que devuelve entidades correctamente.
 *
 * Ejecutar DENTRO del contenedor:
 *   php /var/www/scripts/test_pdf_upload.php
 */

$baseUrl = 'http://127.0.0.1:8080';
$pdfPath = '/tmp/actuacion.pdf';

if (!file_exists($pdfPath)) {
    echo "ERROR: no se encontró el PDF en $pdfPath\n";
    exit(1);
}

// 1. Obtener página inicial y CSRF token
$ch = curl_init($baseUrl . '/pdf-analyzer');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR      => '/tmp/test_cookies2.txt',
    CURLOPT_COOKIEFILE     => '/tmp/test_cookies2.txt',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 30,
]);
$html = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "GET /pdf-analyzer → HTTP " . $info['http_code'] . "\n";

// Extraer CSRF token
preg_match('/_token[^>]*value="([^"]+)"/', $html, $m);
if (empty($m[1])) {
    preg_match('/csrf-token" content="([^"]+)"/', $html, $m);
}
$token = $m[1] ?? '';
echo "CSRF token: " . ($token ? substr($token, 0, 20) . '…' : 'NO ENCONTRADO') . "\n";

if (!$token) {
    echo "No se pudo extraer el CSRF token. Primeros 500 chars:\n";
    echo substr(strip_tags($html), 0, 500) . "\n";
    exit(1);
}

// 2. POST multipart con el PDF
echo "\nSubiendo $pdfPath ...\n";
$ch2 = curl_init($baseUrl . '/pdf-analyzer/process');
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => [
        '_token' => $token,
        'pdf'    => new CURLFile($pdfPath, 'application/pdf', 'actuacion.pdf'),
    ],
    CURLOPT_COOKIEJAR      => '/tmp/test_cookies2.txt',
    CURLOPT_COOKIEFILE     => '/tmp/test_cookies2.txt',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 180,
]);
$resp = curl_exec($ch2);
$code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$finalUrl = curl_getinfo($ch2, CURLINFO_EFFECTIVE_URL);
curl_close($ch2);

echo "POST /pdf-analyzer/process → HTTP $code\n";
echo "URL final: $finalUrl\n\n";

if ($code === 200) {
    // Contar filas de entidades
    preg_match_all('/class="entity-row"/', $resp, $rows);
    $count = count($rows[0]);
    echo "Filas de entidades detectadas: $count\n";

    if ($count === 0) {
        // Buscar mensaje de error en el HTML
        preg_match('/<div class="alert alert-danger[^>]*>(.*?)<\/div>/s', $resp, $errMatch);
        $errMsg = $errMatch[1] ?? '';
        echo "ALERT ERROR: " . trim(strip_tags(html_entity_decode($errMsg))) . "\n";

        // Verificar si hay alguna tabla de entidades vacía
        if (strpos($resp, 'entity-count-badge') !== false) {
            echo "(Tabla de entidades presente pero sin filas)\n";
        }
        // Últimas líneas significativas del PHP de error si hay
        echo "\nPrimeros 1200 chars del body:\n";
        echo substr(strip_tags(html_entity_decode($resp)), 0, 1200) . "\n";
    } else {
        echo "RESULTADO: OK — PDF analizado correctamente con $count entidades.\n";

        // Mostrar algunas entidades detectadas
        preg_match_all('/<tr[^>]*class="entity-row"[^>]*data-label="([^"]*)"[^>]*data-entity-texts="([^"]*)"/', $resp, $entMatches, PREG_SET_ORDER);
        echo "\nPrimeras 10 entidades:\n";
        foreach (array_slice($entMatches, 0, 10) as $ent) {
            $texts = json_decode(html_entity_decode($ent[2]), true);
            echo "  [{$ent[1]}] " . implode(', ', (array)$texts) . "\n";
        }
    }
} else {
    echo "ERROR HTTP $code\n";
    // Buscar el mensaje de error de nginx o Laravel
    if (strpos($resp, 'nginx') !== false || strpos($resp, '500') !== false) {
        echo "Parece error de servidor (nginx / 500)\n";
    }
    echo "Primeros 600 chars:\n";
    echo substr($resp, 0, 600) . "\n";
}
