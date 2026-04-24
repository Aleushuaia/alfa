<?php
require '/var/www/html/vendor/autoload.php';
$w = new \PhpOffice\PhpWord\PhpWord();
$s = $w->addSection();
$s->addText('Juan Garcia es el director ejecutivo de Alfa Corp S.A., ubicada en Buenos Aires, Argentina.');
$s->addText('Su DNI es 28.456.789 y puede contactarse al +54 9 11 1234-5678 o en juan.garcia@alfacorp.com.ar.');
$s->addText('La reunion fue el 15 de marzo de 2024 en la sede central.');
$writer = \PhpOffice\PhpWord\IOFactory::createWriter($w, 'Word2007');
$writer->save('/tmp/test_fake.docx');
echo 'DOCX creado: ' . filesize('/tmp/test_fake.docx') . ' bytes';
