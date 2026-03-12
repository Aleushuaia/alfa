<?php
$base='http://localhost:8080';
$tmpCookie='/tmp/cookies.txt';
$ch = curl_init("$base/pdf-analyzer");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $tmpCookie);
curl_setopt($ch, CURLOPT_COOKIEFILE, $tmpCookie);
$page = curl_exec($ch);
curl_close($ch);
if(!$page){ echo "GET FAILED\n"; exit(1); }
if(preg_match('/meta name="csrf-token" content="([^"]+)"/',$page,$m)) $csrf=$m[1]; else { echo "NO CSRF\n"; exit(2); }
echo "GOT_CSRF:$csrf\n";
echo "COOKIE_JAR_CONTENTS:\n";
if (file_exists($tmpCookie)) {
	echo file_get_contents($tmpCookie) . "\n";
} else {
	echo "(no cookie file)\n";
}

$ch = curl_init("$base/pdf-analyzer/process");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $tmpCookie);
curl_setopt($ch, CURLOPT_COOKIEFILE, $tmpCookie);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	"X-CSRF-TOKEN: $csrf",
	"Referer: http://localhost:8080/pdf-analyzer",
	"Origin: http://localhost:8080",
]);
curl_setopt($ch, CURLOPT_POST, true);
if (!file_exists('/tmp/sample.pdf')) { echo "SAMPLE PDF MISSING\n"; exit(3); }
curl_setopt($ch, CURLOPT_POSTFIELDS, ['pdf' => new CURLFile('/tmp/sample.pdf','application/pdf','sample.pdf')]);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP_CODE: " . ($info['http_code'] ?? 'N/A') . "\n";
// Save full response for inspection
file_put_contents('/tmp/upload_response.full.html', $response ?: '');
// Print first 1000 chars
echo "---RESPONSE_PREVIEW---\n";
echo substr($response, 0, 3000);

