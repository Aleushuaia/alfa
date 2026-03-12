<?php
// Script minimal PDF generator (no dependencias externas)
// Writes scripts/sample_test.pdf with selectable text across two pages

$target = __DIR__ . '/sample_test.pdf';

function escape_pdf_text($s) {
    $s = str_replace('\\', '\\\\', $s);
    $s = str_replace('(', '\\(', $s);
    $s = str_replace(')', '\\)', $s);
    return $s;
}

// Page contents (text lines)
$page1_lines = [
    "Expediente de prueba",
    "Parte: Juan Perez",
    "DNI: 32456789",
    "Domicilio: San Martin 123, Ushuaia",
    "Telefono: +54 2901 123456",
    "Email: juan.perez@example.com",
    "Fecha: 15 de marzo de 2024",
];

$page2_lines = [
    "Anexos y observaciones:",
    "Patente: ABC123DE",
    "Contacto alternativo: maria.sanchez@example.org",
    "Telefono alternativo: 29011234567",
    "Audiencia propuesta: 03/04/2024",
];

function make_stream_from_lines($lines, $font_size=12, $start_y=760) {
    $commands = "BT\n/F1 $font_size Tf\n72 $start_y Td\n";
    $first = true;
    foreach ($lines as $line) {
        $text = escape_pdf_text($line);
        if ($first) {
            $commands .= "($text) Tj\n";
            $first = false;
        } else {
            // move down by 16 units
            $commands .= "0 -16 Td\n($text) Tj\n";
        }
    }
    $commands .= "ET\n";
    return $commands;
}

$obj = [];
// 1 Catalog
$obj[1] = "<< /Type /Catalog /Pages 2 0 R >>\n";
// 2 Pages
$obj[2] = "<< /Type /Pages /Kids [3 0 R 4 0 R] /Count 2 >>\n";
// 3 Page 1
$obj[3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 6 0 R >>\n";
// 4 Page 2
$obj[4] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 7 0 R >>\n";
// 5 Font
$obj[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\n";
// 6 & 7 contents (streams)
$stream6 = make_stream_from_lines($page1_lines, 14, 740);
$stream7 = make_stream_from_lines($page2_lines, 12, 760);

// Build file and track offsets
$fp = fopen($target, 'wb');
if (!$fp) { echo "ERR: could not open $target for writing\n"; exit(1); }

fwrite($fp, "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n");
$offsets = [];

for ($i = 1; $i <= 7; $i++) {
    $offsets[$i] = ftell($fp);
    if ($i == 6) {
        // stream 6
        $content = $stream6;
        $len = strlen($content);
        fwrite($fp, "6 0 obj << /Length $len >>\nstream\n");
        fwrite($fp, $content);
        fwrite($fp, "endstream\nendobj\n");
    } elseif ($i == 7) {
        $content = $stream7;
        $len = strlen($content);
        fwrite($fp, "7 0 obj << /Length $len >>\nstream\n");
        fwrite($fp, $content);
        fwrite($fp, "endstream\nendobj\n");
    } else {
        fwrite($fp, "$i 0 obj ");
        fwrite($fp, $obj[$i]);
        fwrite($fp, "endobj\n");
    }
}

// xref
$xref_pos = ftell($fp);
fwrite($fp, "xref\n0 8\n");
fwrite($fp, sprintf("%010d %05d f \n", 0, 65535));
for ($i = 1; $i <= 7; $i++) {
    fwrite($fp, sprintf("%010d %05d n \n", $offsets[$i], 0));
}

// trailer
fwrite($fp, "trailer\n<< /Size 8 /Root 1 0 R >>\nstartxref\n");
fwrite($fp, (string)$xref_pos . "\n%%EOF\n");

fclose($fp);

if (file_exists($target)) {
    echo "WROTE: $target\n";
} else {
    echo "FAILED to write $target\n";
}
