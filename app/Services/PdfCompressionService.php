<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * PdfCompressionService
 *
 * Comprime archivos PDF usando Ghostscript (gs).
 * Niveles disponibles: screen | ebook | printer | prepress
 */
class PdfCompressionService
{
    /** Niveles ordenados de más compresión a mejor calidad */
    const LEVELS = [
        'screen'   => ['label' => 'Pantalla (máx. compresión)',  'dpi' => 72,  'desc' => '72 dpi – para leer en pantalla'],
        'ebook'    => ['label' => 'eBook (recomendado)',          'dpi' => 150, 'desc' => '150 dpi – balance tamaño/calidad'],
        'printer'  => ['label' => 'Impresión (alta calidad)',     'dpi' => 300, 'desc' => '300 dpi – listo para imprimir'],
        'prepress' => ['label' => 'Pre-prensa (mín. compresión)', 'dpi' => 300, 'desc' => '300 dpi+ – máxima fidelidad'],
    ];

    /**
     * Comprime un PDF y escribe el resultado en $outputPath.
     *
     * @param  string  $inputPath   Ruta absoluta al PDF de entrada.
     * @param  string  $outputPath  Ruta absoluta donde se guardará el PDF comprimido.
     * @param  string  $level       Uno de: screen | ebook | printer | prepress
     * @throws RuntimeException
     */
    public function compress(string $inputPath, string $outputPath, string $level = 'ebook'): void
    {
        if (!array_key_exists($level, self::LEVELS)) {
            throw new RuntimeException("Nivel de compresión inválido: {$level}");
        }

        $this->assertGhostscript();

        $cmd = [
            'gs',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dPDFSETTINGS=/' . $level,
            '-dNOPAUSE',
            '-dQUIET',
            '-dBATCH',
            '-sOutputFile=' . $outputPath,
            $inputPath,
        ];

        $process = new Process($cmd, timeout: 120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(
                'Ghostscript falló: ' . trim($process->getErrorOutput() ?: $process->getOutput())
            );
        }

        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('Ghostscript no generó el archivo de salida.');
        }
    }

    /**
     * Verifica que el binario gs esté disponible.
     */
    private function assertGhostscript(): void
    {
        $process = new Process(['which', 'gs']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(
                'Ghostscript (gs) no está disponible en el servidor. ' .
                'Instale el paquete ghostscript.'
            );
        }
    }
}
