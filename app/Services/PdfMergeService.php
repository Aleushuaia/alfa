<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * PdfMergeService
 *
 * Envía varios PDF (en el orden final deseado) al microservicio Python
 * `pdf_service`, que los une con `pypdf`, agrega una portada con índice
 * visual y marcadores/outline navegables, y devuelve el PDF resultante.
 */
class PdfMergeService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.pdf.url', env('PDF_SERVICE_URL', 'http://localhost:8003')),
            '/'
        );
    }

    /**
     * @param  UploadedFile[]  $files  Archivos en el orden final de fusión.
     * @return array{pdf: string, total_pages: int, documents: array}
     * @throws RuntimeException
     */
    public function merge(array $files): array
    {
        $request = Http::timeout(180);

        foreach ($files as $file) {
            $request = $request->attach(
                'files',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            );
        }

        $response = $request->post("{$this->baseUrl}/merge");

        if ($response->failed()) {
            $detail = $response->json('detail') ?? $response->body();
            throw new RuntimeException("El servicio de fusión de PDF devolvió un error: {$detail}");
        }

        $data = $response->json();

        return [
            'pdf'         => base64_decode($data['pdf_base64']),
            'total_pages' => $data['total_pages'],
            'documents'   => $data['documents'],
        ];
    }
}
