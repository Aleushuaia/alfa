<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

$word = new PhpWord();

// Estilos
$word->addTitleStyle(1, ['bold' => true, 'size' => 18, 'color' => '1F3864'], ['spaceAfter' => 200]);
$word->addTitleStyle(2, ['bold' => true, 'size' => 14, 'color' => '2F5496'], ['spaceAfter' => 120, 'spaceBefore' => 200]);

$section = $word->addSection(['marginTop' => 1200, 'marginBottom' => 1200, 'marginLeft' => 1200, 'marginRight' => 1200]);

// Portada
$section->addTitle('PROYECTO ALFA', 1);
$section->addText('Informe de Stack Tecnológico', ['size' => 14, 'color' => '444444', 'name' => 'Calibri'], ['spaceAfter' => 60]);
$section->addText('Colaborador Inteligente para Unidades Judiciales', ['size' => 12, 'italic' => true, 'name' => 'Calibri']);
$section->addText('Fecha: ' . date('d/m/Y'), ['size' => 10, 'name' => 'Calibri']);
$section->addTextBreak(2);

// Descripción
$section->addTitle('Descripción del Sistema', 2);
$section->addText('Alfa es una plataforma web judicial que procesa, analiza y anonimiza documentos legales. Combina un backend Laravel con microservicios de Inteligencia Artificial (NLP, transcripción y LLM) desplegados en contenedores Docker. El sistema gestiona expedientes, clasifica entidades en listas blancas/negras, transcribe audiencias de audio/video y permite anonimizar escritos judiciales.');
$section->addTextBreak(1);

// 1. Infraestructura
$section->addTitle('1. Infraestructura y Contenerización', 2);
$data1 = [
    ['Docker', 'Latest', 'Motor de contenedores. Empaqueta cada componente con todas sus dependencias en unidades aisladas y portables.'],
    ['Docker Compose', 'v2', 'Orquestador multi-contenedor. Coordina los 4 servicios del sistema (app, postgres, nlp, whisper).'],
    ['Alpine Linux', '3.x', 'Sistema operativo base de los contenedores. Imagen minimalista que reduce la superficie de ataque.'],
    ['Nginx', '1.x', 'Servidor web. Recibe peticiones HTTP, sirve assets estáticos y delega PHP a PHP-FPM.'],
    ['Supervisor', '4.x', 'Gestor de procesos. Mantiene vivos Nginx y PHP-FPM dentro del contenedor, reiniciándolos si fallan.'],
];
addTable($section, ['Tecnología', 'Versión', 'Rol / Descripción'], $data1);

// 2. Backend
$section->addTitle('2. Backend Principal', 2);
$data2 = [
    ['PHP', '8.4 (fpm-alpine)', 'Lenguaje principal del backend en modo FastCGI.'],
    ['PHP-FPM', '8.4', 'FastCGI Process Manager. Gestiona el pool de procesos PHP.'],
    ['Laravel', '12.x', 'Framework MVC principal con enrutamiento, ORM Eloquent y autenticación.'],
    ['Composer', '2', 'Gestor de dependencias PHP.'],
    ['OPcache', 'ext PHP', 'Caché de bytecode PHP. Pre-compila el código para acelerar el rendimiento.'],
];
addTable($section, ['Tecnología', 'Versión', 'Rol / Descripción'], $data2);

// 3. Base de Datos
$section->addTitle('3. Base de Datos', 2);
$data3 = [
    ['PostgreSQL', '16 Alpine', 'Base de datos relacional principal. Almacena usuarios, roles, permisos, entidades y expedientes.'],
    ['MySQL/MariaDB', 'Configurable', 'Conexión opcional al sistema SAE (legado externo).'],
    ['SQLite', '3.x', 'Base de datos de archivo para consultas de dashboard legado.'],
    ['Eloquent ORM', '(Laravel)', 'Mapeador objeto-relacional de Laravel.'],
];
addTable($section, ['Tecnología', 'Versión', 'Rol / Descripción'], $data3);

// 4. Paquetes PHP
$section->addTitle('4. Paquetes PHP de Negocio', 2);
$data4 = [
    ['spatie/laravel-permission', '^7.2', 'Control de acceso basado en roles (RBAC).'],
    ['barryvdh/laravel-dompdf', '^3.1', 'Generación de PDFs desde HTML/CSS.'],
    ['phpoffice/phpword', '^1.4', 'Creación y manipulación de documentos Word (.docx).'],
    ['spatie/pdf-to-text', '^1.53', 'Extracción de texto de PDFs digitales.'],
];
addTable($section, ['Paquete', 'Versión', 'Rol / Descripción'], $data4);

// 5. IA y NLP
$section->addTitle('5. Inteligencia Artificial y NLP', 2);
$data5 = [
    ['Python', '3.11+', 'Lenguaje de los microservicios de IA.'],
    ['FastAPI', '>= 0.110', 'Framework web Python para APIs REST de alto rendimiento.'],
    ['Uvicorn', '>= 0.29', 'Servidor ASGI asíncrono. Ejecuta FastAPI.'],
    ['spaCy', '>= 3.7', 'Librería NLP industrial. Extrae entidades de documentos en español.'],
    ['openai-whisper', 'Latest', 'Modelo de transcripción automática de voz a texto.'],
    ['Ollama', 'Latest', 'Servidor local de modelos LLM (LLaMA 3, Gemma) sin enviar datos externos.'],
    ['ffmpeg', '(sistema)', 'Procesamiento multimedia. Normaliza archivos de audio/video.'],
];
addTable($section, ['Tecnología', 'Versión', 'Rol / Descripción'], $data5);

// 6. OCR
$section->addTitle('6. OCR (Reconocimiento Óptico de Caracteres)', 2);
$data6 = [
    ['Tesseract OCR', '5.x + spa', 'Motor OCR de código abierto con soporte de idioma español.'],
    ['Poppler / pdftoppm', 'utils', 'Convierte PDFs a imágenes PNG a 300 DPI antes de OCR.'],
];
addTable($section, ['Tecnología', 'Versión', 'Rol / Descripción'], $data6);

// 7. Frontend
$section->addTitle('7. Frontend', 2);
$data7 = [
    ['Blade', '(Laravel)', 'Motor de plantillas de Laravel.'],
    ['Vite', '7.x', 'Bundler de assets moderno.'],
    ['AdminLTE', '4.0 RC5', 'Plantilla de panel de administración.'],
    ['Bootstrap', '5.3', 'Framework CSS para grillas y componentes.'],
    ['Alpine.js', '3.14', 'Framework JavaScript ligero y reactivo.'],
    ['ApexCharts', '4.x', 'Librería de gráficos interactivos.'],
    ['Font Awesome', '6.7', 'Biblioteca de iconos vectoriales.'],
    ['Axios', '1.11', 'Cliente HTTP JavaScript.'],
];
addTable($section, ['Tecnología', 'Versión', 'Rol / Descripción'], $data7);

// 8. Testing
$section->addTitle('8. Testing', 2);
$data8 = [
    ['PHPUnit', '11.x', 'Framework de pruebas unitarias e integración.'],
    ['Mockery', '1.6', 'Librería de mocking para tests.'],
    ['Faker', '1.23', 'Generador de datos falsos.'],
];
addTable($section, ['Tecnología', 'Versión', 'Rol / Descripción'], $data8);

// 9. Arquitectura
$section->addTitle('9. Arquitectura del Sistema', 2);
$section->addText('Contenedores Docker:');
$section->addListItem('alfa_app (puerto 8080): Nginx + PHP-FPM 8.4 + Laravel 12 + Supervisor');
$section->addListItem('alfa_postgres (puerto 5433): PostgreSQL 16 con persistencia en volumen');
$section->addListItem('alfa_nlp (puerto 8001): Python FastAPI + spaCy (análisis NLP)');
$section->addListItem('alfa_whisper (puerto 8002): Python FastAPI + Whisper (transcripción)');

$section->addTextBreak(1);
$section->addText('El contenedor alfa_app se comunica con los microservicios vía HTTP interno en la red Docker "alfa_network". El LLM (Ollama) se integra como servicio externo configurable, garantizando que datos sensibles judiciales no abandonen la infraestructura.');

// Guardar
$outputPath = __DIR__ . '/stack_tecnologico_alfa.docx';
$writer = IOFactory::createWriter($word, 'Word2007');
$writer->save($outputPath);

echo "✓ DOCX generado: " . basename($outputPath) . " (" . number_format(filesize($outputPath) / 1024, 1) . " KB)\n";

/**
 * Función helper para crear tablas
 */
function addTable($section, $headers, $data) {
    $tableStyle = [
        'borderSize'  => 6,
        'borderColor' => 'CCCCCC',
        'cellMargin'  => 100,
    ];
    
    $table = $section->addTable($tableStyle);
    
    // Header
    $table->addRow(400);
    $headerBg = ['bgColor' => '2F5496'];
    $headerFont = ['bold' => true, 'color' => 'FFFFFF', 'size' => 10, 'name' => 'Calibri'];
    $cellFont = ['size' => 10, 'name' => 'Calibri'];
    
    foreach ($headers as $header) {
        $cell = $table->addCell(3300, $headerBg);
        $cell->addText($header, $headerFont);
    }
    
    // Data rows
    foreach ($data as $i => $row) {
        $table->addRow();
        $rowBg = ($i % 2 === 1) ? ['bgColor' => 'E7F0F7'] : [];
        foreach ($row as $j => $col) {
            $cell = $table->addCell(3300, $rowBg);
            $style = ($j === 0) ? array_merge($cellFont, ['bold' => true]) : $cellFont;
            $cell->addText($col, $style);
        }
    }
    
    $section->addTextBreak(1);
}
?>
