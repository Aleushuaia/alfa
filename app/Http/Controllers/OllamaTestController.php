<?php

namespace App\Http\Controllers;

use App\Services\OllamaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OllamaTestController extends Controller
{
    private OllamaService $ollama;

    public function __construct(OllamaService $ollama)
    {
        $this->ollama = $ollama;
    }

    /**
     * Muestra la vista de prueba del modelo Ollama.
     */
    public function index(): \Illuminate\View\View
    {
        $config = $this->ollama->getConfig();

        return view('ollama.test', [
            'ollamaUrl'   => $config['url'],
            'ollamaModel' => $config['model'],
        ]);
    }

    /**
     * Recibe el mensaje del usuario, lo envía a Ollama y retorna la respuesta JSON.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:5000'],
        ], [
            'message.required' => 'El mensaje no puede estar vacío.',
            'message.max'      => 'El mensaje no puede superar los 5000 caracteres.',
        ]);

        // Sanitizar: quitar tags HTML aunque el front ya envíe texto plano
        $prompt = strip_tags(trim($validated['message']));

        if ($prompt === '') {
            return response()->json(['error' => 'El mensaje no puede estar vacío.'], 422);
        }

        Log::info('OllamaTest: solicitud recibida', [
            'user'  => $request->user()?->id,
            'chars' => strlen($prompt),
        ]);

        try {
            $reply = $this->ollama->chat($prompt);

            return response()->json([
                'success'  => true,
                'response' => $reply,
                'model'    => env('OLLAMA_MODEL', 'llama3'),
            ]);
        } catch (RuntimeException $e) {
            Log::warning('OllamaTest: error al consultar Ollama', [
                'error' => $e->getMessage(),
                'user'  => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 503);
        }
    }
}
