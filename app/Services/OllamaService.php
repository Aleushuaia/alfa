<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * OllamaService
 *
 * Envía prompts a un servidor Ollama accesible en red local
 * y retorna la respuesta parseada del modelo LLM configurado.
 *
 * Configuración en .env:
 *   OLLAMA_URL      URL base del servidor (ej: http://192.168.0.121:11434)
 *   OLLAMA_MODEL    Nombre del modelo     (ej: gemma:7b)
 *   OLLAMA_TIMEOUT  Timeout en segundos   (ej: 120)
 */
class OllamaService
{
    private string $url;
    private string $model;
    private int    $timeout;

    public function __construct()
    {
        $this->url     = rtrim((string) env('OLLAMA_URL', 'http://localhost:11434'), '/');
        $this->model   = (string) env('OLLAMA_MODEL', 'llama3');
        $this->timeout = (int)   env('OLLAMA_TIMEOUT', 120);
    }

    /**
     * Envía un prompt al modelo Ollama y retorna el texto de respuesta.
     *
     * @param  string  $prompt  Texto del usuario (ya saneado).
     * @return string           Respuesta del modelo.
     * @throws RuntimeException Si el servidor no responde o la respuesta es inválida.
     */
    public function chat(string $prompt): string
    {
        $endpoint = $this->url . '/api/generate';

        $payload = [
            'model'  => $this->model,
            'prompt' => $prompt,
            'stream' => false,
        ];

        Log::info('Ollama: enviando prompt', [
            'endpoint' => $endpoint,
            'model'    => $this->model,
            'chars'    => strlen($prompt),
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($endpoint, $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Ollama: error de conexión', ['error' => $e->getMessage()]);
            throw new RuntimeException(
                'No se pudo conectar con el servidor Ollama. Verificá que esté activo en ' . $this->url . '.'
            );
        }

        if ($response->failed()) {
            Log::error('Ollama: respuesta HTTP errónea', [
                'status' => $response->status(),
                'body'   => substr((string) $response->body(), 0, 500),
            ]);
            throw new RuntimeException(
                'El servidor Ollama respondió con un error (HTTP ' . $response->status() . ').'
            );
        }

        $data = $response->json();

        if (!isset($data['response'])) {
            Log::error('Ollama: respuesta sin campo "response"', [
                'body' => substr((string) $response->body(), 0, 500),
            ]);
            throw new RuntimeException('La respuesta del modelo tiene un formato inesperado.');
        }

        $text = trim((string) $data['response']);

        Log::info('Ollama: respuesta recibida', [
            'model'         => $data['model']              ?? $this->model,
            'chars'         => strlen($text),
            'total_duration'=> $data['total_duration']     ?? null,
            'done'          => $data['done']               ?? null,
        ]);

        return $text;
    }

    /**
     * Retorna la URL y modelo configurados (útil para mostrar estado).
     */
    public function getConfig(): array
    {
        return [
            'url'   => $this->url,
            'model' => $this->model,
        ];
    }
}
