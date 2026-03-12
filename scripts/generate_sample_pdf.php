<?php

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

$html = <<<'HTML'
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>PDF de prueba — SAE Kayen</title>
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12pt; line-height:1.5; }
    h1 { font-size: 18pt; }
    .meta { color: #555; margin-bottom: 12px; }
    .section { margin-bottom: 14px; }
    .small { font-size: 10pt; color:#333 }
    .sig { margin-top: 32px; }
  </style>
</head>
<body>
  <h1>Expediente de prueba</h1>
  <div class="meta">Documento generado para pruebas de extracción y anonimización</div>

  <div class="section">
    <strong>Parte:</strong> Juan Pérez
    <br>
    <strong>DNI:</strong> 32456789
    <br>
    <strong>Domicilio:</strong> San Martín 123, Ushuaia
    <br>
    <strong>Teléfono:</strong> +54 2901 123456
    <br>
    <strong>Email:</strong> juan.perez@example.com
  </div>

  <div class="section">
    <strong>Hecho narrado:</strong>
    <p class="small">El día 15 de marzo de 2024, se presentó ante el Juzgado el ciudadano Juan Pérez. El expediente N° 0001/2024 contiene las actuaciones iniciales.</p>
  </div>

  <div style="page-break-after: always;"></div>

  <h2>Segunda página — Anexos</h2>
  <div class="section">
    <p>Se adjunta copia de la cédula (DNI: 32456789) y comprobantes de notificación.</p>
    <p>Patente vehículo: ABC123DE</p>
  </div>

  <div class="section">
    <p>Contacto alternativo: maria.sanchez@example.org — Teléfono: 29011234567</p>
    <p>Fecha de la audiencia propuesta: 03/04/2024</p>
  </div>

  <div class="sig">Atentamente,<br>Oficina de Pruebas — SAE Kayen</div>
</body>
</html>
HTML;

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$output = $dompdf->output();
$target = __DIR__ . '/sample_test.pdf';
file_put_contents($target, $output);

echo "WROTE: " . $target . PHP_EOL;
