<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * NlpEntityService
 *
 * Envía texto al microservicio Python (FastAPI + spaCy) y devuelve
 * el HTML con entidades etiquetadas junto con el listado de entidades.
 */
class NlpEntityService
{
    /** URL base del microservicio NLP (configurada en .env como NLP_SERVICE_URL). */
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.nlp.url', env('NLP_SERVICE_URL', 'http://localhost:8001')), '/');
    }

    /**
     * Envía el texto al microservicio y devuelve la respuesta.
     *
     * @param  string $text  Texto plano extraído del PDF.
     * @return array{html: string, entities: array<mixed>}
     *
     * @throws RuntimeException Si el microservicio no responde o devuelve error.
     */
    public function analyze(string $text): array
    {
        try {
            $response = Http::timeout(60)
                ->post("{$this->baseUrl}/analyze", [
                    'text' => $text,
                ]);

            if ($response->failed()) {
                throw new RuntimeException('El microservicio NLP devolvió un error HTTP ' . $response->status());
            }

            $data = $response->json();

            if (!isset($data['html'])) {
                throw new RuntimeException('Respuesta inesperada del microservicio NLP.');
            }

            return [
                'html'     => $data['html'],
                'entities' => $data['entities'] ?? [],
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new RuntimeException('No se pudo analizar el texto: no se pudo conectar con el servicio NLP.');
        } catch (\Exception $e) {
            throw new RuntimeException('Error inesperado al comunicar con el servicio NLP: ' . $e->getMessage());
        }
    }
}
