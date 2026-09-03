<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * DocxConverterService
 *
 * Envía un documento (.doc / .rtf / .odt / .docx) al microservicio Python
 * `converter_service`, que detecta el formato real por el contenido interno
 * del archivo y lo convierte a .docx con LibreOffice headless.
 *
 * Devuelve el diagnóstico completo del microservicio:
 *   [
 *     'source'   => ['filename', 'detected_format', 'format_label', 'size_bytes', 'extension_mismatch'],
 *     'result'   => ['status' => 'converted'|'passthrough'|'rejected', 'filename', 'size_bytes', 'docx_base64'],
 *     'warnings' => string[],
 *     'message'  => string,
 *   ]
 */
class DocxConverterService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.converter.url', env('CONVERTER_SERVICE_URL', 'http://localhost:8004')),
            '/'
        );
    }

    /**
     * @return array{source: array, result: array, warnings: array, message: string}
     * @throws RuntimeException
     */
    public function convert(UploadedFile $file): array
    {
        $response = Http::timeout(180)
            ->attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )
            ->post("{$this->baseUrl}/convert");

        if ($response->failed()) {
            $detail = $response->json('detail') ?? $response->body();
            throw new RuntimeException("El servicio de conversión devolvió un error: {$detail}");
        }

        return $response->json();
    }
}
