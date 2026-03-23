<?php
/**
 * Genera un PDF escaneado de prueba para verificar el pipeline OCR.
 * Crea una imagen PNG con texto y la convierte a PDF.
 * Luego llama a OcrExtractorService para verificar que funcione.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\OcrExtractorService;

// 1. Crear imagen PNG con texto GD
$w = 800; $h = 400;
$img = imagecreatetruecolor($w, $h);
$white = imagecolorallocate($img, 255, 255, 255);
$black = imagecolorallocate($img, 0, 0, 0);
$gray  = imagecolorallocate($img, 40, 40, 40);

imagefill($img, 0, 0, $white);

// Texto de prueba a escala legible para Tesseract
$lines = [
    "TEXTO DE PRUEBA - OCR TESSERACT",
    "Nombre: Juan Garcia",
    "Expediente: 12345/2026",
    "Fecha: 20 de marzo de 2026",
    "El presente documento certifica que el sistema",
    "de extraccion OCR funciona correctamente.",
    "Numeros: 1234567890",
    "Simbolos permitidos: @ # % & *",
];

$fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
$hasTTF   = function_exists('imagettftext') && file_exists($fontPath);

$y = 40;
foreach ($lines as $i => $line) {
    $fontSize = ($i === 0) ? 22 : 16;
    $color    = ($i === 0) ? $black : $gray;

    if ($hasTTF) {
        imagettftext($img, $fontSize, 0, 30, $y + $fontSize, $color, $fontPath, $line);
    } else {
        // Fallback: fuente built-in (más pequeña)
        imagestring($img, 5, 30, $y, $line, $color);
    }
    $y += ($i === 0) ? 50 : 35;
}

$pngPath = '/tmp/ocr_test_image.png';
imagepng($img, $pngPath, 0);
imagedestroy($img);

echo "[1] Imagen PNG creada: $pngPath (" . filesize($pngPath) . " bytes)\n";

// 2. Convertir PNG a PDF de una página con ImageMagick o pdftoppm inverso
// Usamos img2pdf si existe, sino generamos PDF mínimo con la imagen embebida
$pdfPath = '/tmp/ocr_test_input.pdf';

// Intentar con img2pdf
exec('which img2pdf 2>/dev/null', $out, $code);
if ($code === 0) {
    exec("img2pdf " . escapeshellarg($pngPath) . " -o " . escapeshellarg($pdfPath) . " 2>&1", $o, $c);
    echo "[2] img2pdf: exit=$c " . implode(' ', $o) . "\n";
} else {
    // Usar convert (ImageMagick) si está disponible
    exec('which convert 2>/dev/null', $out2, $code2);
    if ($code2 === 0) {
        exec("convert " . escapeshellarg($pngPath) . " " . escapeshellarg($pdfPath) . " 2>&1", $o, $c);
        echo "[2] convert (ImageMagick): exit=$c " . implode(' ', $o) . "\n";
    } else {
        // Fallback: embedir PNG en PDF manualmente (spec PDF 1.4)
        echo "[2] Generando PDF manualmente con imagen embebida...\n";

        $pngData   = file_get_contents($pngPath);
        $pngLen    = strlen($pngData);
        $pngB64    = ''; // no usar base64 en PDF/1.4 streams; usar FlateDecode/raw

        // PDF con imagen PNG embebida (modo raw, sin compresión adicional)
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        // Objeto 1: Catalog
        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // Objeto 2: Pages
        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // Objeto 5: Imagen XObject (PNG → FlateDecode no; usar raw con /DCTDecode no aplica para PNG)
        // Para simplificar embebemos como /FlateDecode si disponemos de zlib, si no como raw
        if (function_exists('gzcompress')) {
            $compressed = gzcompress($pngData, 9);
            // Necesitamos un decoder predictor para PNG; es complejo. Mejor encodar como imagen raw RGBA.
            // Lo más simple: convertir a formato sin comprimir que PDF pueda interpretar.
            // Usamos el PNG tal cual con /Filter /Flate... pero PDF no habla PNG nativamente.
            // La forma correcta es decodificar PNG y embeber como /DeviceRGB raw stream.
            [$iw, $ih] = getimagesize($pngPath);
            $gd = imagecreatefrompng($pngPath);
            $rawPixels = '';
            for ($row = 0; $row < $ih; $row++) {
                for ($col = 0; $col < $iw; $col++) {
                    $c = imagecolorat($gd, $col, $row);
                    $rawPixels .= chr(($c >> 16) & 0xFF) . chr(($c >> 8) & 0xFF) . chr($c & 0xFF);
                }
            }
            imagedestroy($gd);
            $streamData = gzcompress($rawPixels, 9);
            $filter     = '/FlateDecode';
            $cs         = '/DeviceRGB';
            $bpc        = 8;
        } else {
            [$iw, $ih] = getimagesize($pngPath);
            $gd = imagecreatefrompng($pngPath);
            $streamData = '';
            for ($row = 0; $row < $ih; $row++) {
                for ($col = 0; $col < $iw; $col++) {
                    $c = imagecolorat($gd, $col, $row);
                    $streamData .= chr(($c >> 16) & 0xFF) . chr(($c >> 8) & 0xFF) . chr($c & 0xFF);
                }
            }
            imagedestroy($gd);
            $filter = '/ASCIIHexDecode'; // fallback simple
            $cs     = '/DeviceRGB';
            $bpc    = 8;
        }

        // Objeto 5: imagen
        $offsets[5] = strlen($pdf);
        $pdf .= "5 0 obj\n<< /Type /XObject /Subtype /Image /Width $iw /Height $ih"
              . " /ColorSpace $cs /BitsPerComponent $bpc /Filter $filter /Length " . strlen($streamData)
              . " >>\nstream\n" . $streamData . "\nendstream\nendobj\n";

        // Objeto 4: Content stream
        $pageW = 612; $pageH = 792;
        $scale = min($pageW / $iw, $pageH / $ih);
        $dw = round($iw * $scale); $dh = round($ih * $scale);
        $x  = round(($pageW - $dw) / 2); $yp = round(($pageH - $dh) / 2);
        $content = "q\n$dw 0 0 $dh $x $yp cm\n/Img1 Do\nQ\n";
        $offsets[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n$content\nendstream\nendobj\n";

        // Objeto 3: Page
        $offsets[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageW $pageH]"
              . " /Contents 4 0 R /Resources << /XObject << /Img1 5 0 R >> >> >>\nendobj\n";

        // xref
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach ([1,2,3,4,5] as $n) {
            $pdf .= str_pad($offsets[$n], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF\n";
        file_put_contents($pdfPath, $pdf);
        echo "[2] PDF manual generado con imagen embebida\n";
    }
}

if (!file_exists($pdfPath) || filesize($pdfPath) < 100) {
    echo "ERROR: No se pudo crear el PDF de prueba\n";
    exit(1);
}

echo "[2] PDF creado: $pdfPath (" . filesize($pdfPath) . " bytes)\n";

// 3. Probar el servicio OCR completo
echo "\n[3] Probando OcrExtractorService...\n";
try {
    // Bootstrap Laravel mínimo para usar el servicio
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $service = new OcrExtractorService();
    $text    = $service->extractFromPdf($pdfPath);
    echo "[3] EXITO! Texto extraido (" . strlen($text) . " chars):\n";
    echo str_repeat('-', 60) . "\n";
    echo $text . "\n";
    echo str_repeat('-', 60) . "\n";
} catch (\Exception $e) {
    echo "[3] ERROR: " . $e->getMessage() . "\n";

    // Diagnóstico adicional: probar directamente
    echo "\n[3b] Diagnóstico directo (sin servicio)...\n";
    $tmpDir = '/tmp/ocr_test_direct_' . uniqid();
    mkdir($tmpDir, 0700, true);
    $imgBase = $tmpDir . '/page';

    exec("pdftoppm -r 150 -png " . escapeshellarg($pdfPath) . " " . escapeshellarg($imgBase) . " 2>&1", $ppmOut, $ppmCode);
    echo "pdftoppm exit=$ppmCode: " . implode(' ', $ppmOut) . "\n";

    $images = glob($tmpDir . '/page*.png');
    echo "Imágenes: " . count($images) . "\n";

    if (!empty($images)) {
        $outBase = $tmpDir . '/out';
        exec("tesseract " . escapeshellarg($images[0]) . " " . escapeshellarg($outBase) . " -l spa 2>&1", $tOut, $tCode);
        echo "tesseract exit=$tCode: " . implode(' ', $tOut) . "\n";
        $txtFile = $outBase . '.txt';
        if (file_exists($txtFile)) {
            echo "Texto: " . file_get_contents($txtFile) . "\n";
        }
    }

    exec("rm -rf " . escapeshellarg($tmpDir));
}

// Cleanup
if (file_exists($pdfPath))  { unlink($pdfPath); }
if (file_exists($pngPath))  { unlink($pngPath); }

echo "\n=== FIN TEST OCR ===\n";
