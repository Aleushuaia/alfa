<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranscripcionController extends Controller
{
    /**
     * Muestra el formulario de transcripción.
     */
    public function index()
    {
        return view('transcripcion.index');
    }

    /**
     * Recibe el archivo multimedia, lo envía al microservicio Whisper
     * y devuelve el texto transcripto como JSON.
     */
    public function transcribir(Request $request)
    {
        // Siempre responder JSON aunque la validación falle
        $request->headers->set('Accept', 'application/json');

        $request->validate([
            'media' => [
                'required',
                'file',
                'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/wave,audio/x-wav,audio/ogg,audio/webm,audio/m4a,audio/mp4,video/mp4,video/x-matroska,video/webm,video/ogg,application/octet-stream',
                'max:204800', // 200 MB en KB
            ],
        ]);

        $file        = $request->file('media');
        $whisperUrl  = rtrim(env('WHISPER_SERVICE_URL', 'http://whisper:8002'), '/');

        try {
            Log::info('Enviando archivo a Whisper', [
                'filename' => $file->getClientOriginalName(),
                'size_mb'  => round($file->getSize() / 1048576, 2),
                'mime'     => $file->getMimeType(),
                'url'      => $whisperUrl,
            ]);

            $response = Http::timeout(600)
                ->attach(
                    'file',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                )
                ->post("{$whisperUrl}/transcribe");

            if ($response->failed()) {
                $detail = $response->json('detail') ?? $response->body();
                Log::error('Whisper service error', ['status' => $response->status(), 'detail' => $detail]);
                return response()->json([
                    'error' => 'El servicio de transcripción devolvió un error: ' . $detail,
                ], 502);
            }

            $result = $response->json();
            Log::info('Whisper transcripción OK', ['chars' => strlen($result['text'] ?? '')]);
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Whisper service unreachable', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'No se pudo conectar con el servicio de transcripción: ' . $e->getMessage(),
            ], 503);
        }
    }
}
