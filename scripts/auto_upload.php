<?php
// Auto upload script: GET form, extract CSRF token, POST sample_test.pdf, save response
$base = 'http://localhost:8080';
$formUrl = $base . '/pdf-analyzer';
$processUrl = $base . '/pdf-analyzer/process';
$cookieFile = '/tmp/auto_cookies.txt';
$formFile = '/tmp/form_resp.html';
$processFile = '/tmp/process_resp.html';
$pdfPath = __DIR__ . '/sample_test.pdf';

// 1) GET form
$ch = curl_init($formUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
$formHtml = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($httpCode !== 200) {
    echo "GET form returned HTTP $httpCode\n";
    exit(1);
}
file_put_contents($formFile, $formHtml);

// 2) Extract CSRF token
if (preg_match('/<meta\s+name="csrf-token"\s+content="([^"]+)"/i', $formHtml, $m)) {
    $token = $m[1];
    echo "TOKEN: $token\n";
} else {
    echo "CSRF token not found\n";
    exit(1);
}

// 3) POST multipart with token and file
$ch = curl_init($processUrl);
$post = [
    '_token' => $token,
    'pdf' => new CURLFile($pdfPath, 'application/pdf', basename($pdfPath)),
];
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
file_put_contents($processFile, $response ?: '');

echo "POST HTTP: $httpCode\n";

if ($response === false) {
    echo "No response body\n";
    exit(1);
}

// 4) Check for entity spans
$count = preg_match_all('/class="entity/', $response);
echo "Entity spans found: $count\n";

// 5) Print a small snippet
$snippet = substr($response, 0, 800);
echo "--- SNIPPET ---\n" . $snippet . "\n";

exit(0);
