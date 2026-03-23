<?php
/**
 * Script de prueba del servicio de transcripción Whisper.
 * Ejecución: php artisan tinker --execute="require '/var/www/scripts/test_transcripcion.php';"
 * O directamente: php /var/www/scripts/test_transcripcion.php
 */

// Bootstrap Laravel
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$whisperUrl = rtrim(env('WHISPER_SERVICE_URL', 'http://whisper:8002'), '/');

echo "=== Test Transcripción Whisper ===\n";
echo "URL: {$whisperUrl}\n\n";

// 1. Health check
echo "1. Health check...\n";
try {
    $health = Http::timeout(10)->get("{$whisperUrl}/health");
    echo "   Status: " . $health->status() . "\n";
    echo "   Body:   " . $health->body() . "\n\n";
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Crear un archivo WAV mínimo de silencio (44 bytes header + 4410 bytes de datos = ~0.05s)
echo "2. Generando archivo WAV de prueba...\n";
$sampleRate   = 8000;   // 8 kHz
$duration     = 2;      // 2 segundos
$numSamples   = $sampleRate * $duration;
$numChannels  = 1;
$bitsPerSample = 16;
$byteRate     = $sampleRate * $numChannels * ($bitsPerSample / 8);
$blockAlign   = $numChannels * ($bitsPerSample / 8);
$dataSize     = $numSamples * $blockAlign;

$wav  = 'RIFF';
$wav .= pack('V', 36 + $dataSize);   // ChunkSize
$wav .= 'WAVE';
$wav .= 'fmt ';
$wav .= pack('V', 16);               // Subchunk1Size (PCM)
$wav .= pack('v', 1);                // AudioFormat (PCM=1)
$wav .= pack('v', $numChannels);
$wav .= pack('V', $sampleRate);
$wav .= pack('V', $byteRate);
$wav .= pack('v', $blockAlign);
$wav .= pack('v', $bitsPerSample);
$wav .= 'data';
$wav .= pack('V', $dataSize);
$wav .= str_repeat("\x00", $dataSize); // Silencio

$tmpFile = tempnam(sys_get_temp_dir(), 'test_whisper_') . '.wav';
file_put_contents($tmpFile, $wav);
echo "   Creado: {$tmpFile} (" . strlen($wav) . " bytes)\n\n";

// 3. Enviar al servicio Whisper
echo "3. Enviando a Whisper /transcribe...\n";
try {
    $t0 = microtime(true);
    $resp = Http::timeout(120)
        ->attach('file', file_get_contents($tmpFile), 'test.wav')
        ->post("{$whisperUrl}/transcribe");

    $elapsed = round(microtime(true) - $t0, 2);
    echo "   HTTP Status: " . $resp->status() . " (en {$elapsed}s)\n";
    echo "   Body: " . $resp->body() . "\n\n";

    $json = $resp->json();
    if (isset($json['text'])) {
        echo "   ✅ Transcripción exitosa!\n";
        echo "   Texto: '" . ($json['text'] ?: '(silencio - texto vacío es normal)') . "'\n";
        echo "   Idioma: " . ($json['language'] ?? 'N/A') . "\n";
        echo "   Segmentos: " . ($json['segments_count'] ?? 0) . "\n";
    } elseif (isset($json['error'])) {
        echo "   ❌ Error del servicio: " . $json['error'] . "\n";
    } else {
        echo "   ⚠️  Respuesta inesperada: " . $resp->body() . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Excepción: " . $e->getMessage() . "\n";
} finally {
    @unlink($tmpFile);
}

echo "\n=== Fin del test ===\n";
