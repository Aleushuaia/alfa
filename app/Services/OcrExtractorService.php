<?php

namespace App\Services;

use RuntimeException;

/**
 * OcrExtractorService
 *
 * Extrae texto de archivos PDF escaneados usando Tesseract OCR.
 * Flujo: PDF → imágenes PNG (pdftoppm, 300 DPI) → Tesseract → texto.
 */
class OcrExtractorService
{
    /**
     * Extrae el texto de un PDF mediante OCR.
     *
     * @param  string  $pdfPath  Ruta absoluta al PDF.
     * @param  string  $lang     Idioma Tesseract (default: spa).
     * @return string            Texto extraído y limpio.
     * @throws RuntimeException
     */
    public function extractFromPdf(string $pdfPath, string $lang = 'spa'): string
    {
        $this->assertBinaries();

        $tmpDir = sys_get_temp_dir() . '/ocr_' . uniqid('', true);
        if (!mkdir($tmpDir, 0700, true)) {
            throw new RuntimeException('No se pudo crear el directorio temporal de OCR.');
        }

        try {
            $imgBase = $tmpDir . '/page';

            // 1. Convertir PDF → PNG a 300 DPI con pdftoppm
            $cmd = sprintf(
                'pdftoppm -r 300 -png %s %s 2>&1',
                escapeshellarg($pdfPath),
                escapeshellarg($imgBase)
            );
            exec($cmd, $ppmOut, $ppmCode);

            if ($ppmCode !== 0) {
                throw new RuntimeException(
                    'Error al convertir PDF a imágenes: ' . implode(' ', $ppmOut)
                );
            }

            $images = glob($tmpDir . '/page-*.png');
            if (empty($images)) {
                // Intentar también sin guion (versiones antiguas de pdftoppm)
                $images = glob($tmpDir . '/page*.png');
            }
            if (empty($images)) {
                throw new RuntimeException(
                    'El PDF no pudo convertirse a imágenes. Verifique que el archivo no esté protegido.'
                );
            }
            sort($images);

            // 2. OCR por página con Tesseract
            $pageTexts = [];
            $lastTesseractError = '';
            foreach ($images as $img) {
                $outBase = $img . '_ocr';
                $cmd = sprintf(
                    'tesseract %s %s -l %s --psm 3 --oem 1 2>&1',
                    escapeshellarg($img),
                    escapeshellarg($outBase),
                    escapeshellarg($lang)
                );
                exec($cmd, $tOut, $tCode);

                if ($tCode !== 0) {
                    $lastTesseractError = implode(' ', $tOut);
                    continue;
                }

                $txtFile = $outBase . '.txt';
                if (file_exists($txtFile)) {
                    $pageContent = trim(file_get_contents($txtFile));
                    if ($pageContent !== '') {
                        $pageTexts[] = $pageContent;
                    }
                }
            }

            if (empty($pageTexts)) {
                $detail = $lastTesseractError
                    ? " Detalle: {$lastTesseractError}"
                    : ' El documento puede ser un PDF de texto nativo sin imágenes (use el extractor estándar).';
                throw new RuntimeException(
                    'Tesseract no pudo extraer texto del documento.' . $detail
                );
            }

            return $this->clean(implode("\n\n", $pageTexts));

        } finally {
            $this->removeDir($tmpDir);
        }
    }

    /**
     * Verifica que los binarios necesarios estén disponibles.
     */
    private function assertBinaries(): void
    {
        $paths = ['/usr/bin', '/usr/local/bin', '/bin'];
        foreach (['pdftoppm', 'tesseract'] as $bin) {
            $found = false;
            foreach ($paths as $dir) {
                if (file_exists($dir . '/' . $bin)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                // Fallback: usar which via sh
                exec('sh -c "command -v ' . escapeshellarg($bin) . '"', $out, $code);
                $found = ($code === 0);
            }
            if (!$found) {
                throw new RuntimeException(
                    "El binario '{$bin}' no está disponible en el servidor. " .
                    'Instale poppler-utils y tesseract-ocr.'
                );
            }
        }
    }

    /**
     * Limpia y normaliza el texto OCR.
     */
    private function clean(string $text): string
    {
        $text = str_replace("\x00", '', $text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $lines = explode("\n", $text);
        $lines = array_map('rtrim', $lines);
        return trim(implode("\n", $lines));
    }

    /**
     * Elimina recursivamente un directorio.
     */
    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
