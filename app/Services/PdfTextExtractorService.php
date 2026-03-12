<?php

namespace App\Services;

use Spatie\PdfToText\Pdf;
use RuntimeException;

/**
 * PdfTextExtractorService
 *
 * Extrae el texto plano de un archivo PDF usando Spatie\PdfToText
 * (que internamente llama a `pdftotext` de poppler-utils).
 */
class PdfTextExtractorService
{
    /**
     * Extrae el texto de un PDF dado su ruta absoluta en disco.
     *
     * @param  string $pdfPath  Ruta absoluta al archivo PDF.
     * @return string           Texto extraído.
     *
     * @throws RuntimeException Si la extracción falla.
     */
    public function extract(string $pdfPath): string
    {
        try {
            $raw = (new Pdf())
                ->setPdf($pdfPath)
                ->setOptions(['-nopgbrk', '-enc UTF-8'])
                ->text();

            $text = $this->cleanText($raw);

            if (empty($text)) {
                throw new RuntimeException('El documento no contiene texto extraíble (puede ser un PDF de imágenes escaneadas).');
            }

            return $text;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RuntimeException('No se pudo procesar el documento: ' . $e->getMessage());
        }
    }

    /**
     * Limpia el texto extraído por pdftotext:
     *  - Elimina bytes nulos y saltos de formulario (form feeds)
     *  - Normaliza saltos de línea
     *  - Colapsa más de dos líneas en blanco consecutivas
     *  - Elimina espacios al inicio/fin
     */
    private function cleanText(string $raw): string
    {
        // Eliminar bytes nulos
        $text = str_replace("\x00", '', $raw);

        // Form feeds (\f) que pdftotext usa como separador de página → doble salto
        $text = str_replace("\f", "\n\n", $text);

        // Normalizar \r\n y \r sueltos a \n
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Colapsar 3 o más líneas en blanco consecutivas en 2
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Eliminar líneas que solo contengan caracteres de control/espacios invisibles
        $lines = explode("\n", $text);
        $lines = array_map(static fn(string $l) => rtrim($l), $lines);
        $text  = implode("\n", $lines);

        return trim($text);
    }
}
