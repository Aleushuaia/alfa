<?php

namespace App\Http\Controllers;

use App\Services\OcrExtractorService;
use App\Services\OllamaService;
use App\Services\PromptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SujetosProcesalesController extends Controller
{
    /**
     * Caracteres máximos del texto extraído que se envían al modelo.
     * gemma:7b tiene una ventana de contexto limitada; textos muy largos
     * provocan timeouts o respuestas vacías.
     */
    private const MAX_TEXT_CHARS = 4000;

    /**
     * Timeout en segundos para la llamada a Ollama en este pipeline.
     * Es mayor que el default porque el texto + prompt son más largos.
     */
    private const OLLAMA_TIMEOUT = 600;

    public function __construct(
        private readonly OcrExtractorService $ocr,
        private readonly OllamaService       $ollama,
        private readonly PromptService       $promptService,
    ) {}

    /**
     * Muestra la pantalla principal de extracción de sujetos procesales.
     */
    public function index(): View
    {
        $prompts = $this->promptService->all();

        return view('sujetos-procesales.index', compact('prompts'));
    }

    /**
     * Recibe el PDF y el prompt seleccionado, extrae texto vía OCR,
     * construye el prompt completo, lo envía a Ollama y devuelve el JSON resultante.
     */
    public function extraer(Request $request): JsonResponse
    {
        $request->validate([
            'pdf'       => ['required', 'file', 'mimes:pdf', 'max:51200'],
            'prompt_id' => ['required', 'string', 'exists:prompts,id'],
        ], [
            'pdf.required'       => 'Debe seleccionar un archivo PDF.',
            'pdf.mimes'          => 'Solo se aceptan archivos PDF.',
            'pdf.max'            => 'El archivo no debe superar los 50 MB.',
            'prompt_id.required' => 'Debe seleccionar un prompt.',
            'prompt_id.exists'   => 'El prompt seleccionado no existe.',
        ]);

        $file = $request->file('pdf');

        // Almacenar temporalmente
        Storage::disk('local')->makeDirectory('temp-sujetos');
        $tmpPath = $file->store('temp-sujetos', 'local');
        $absPath = Storage::disk('local')->path($tmpPath);

        try {
            Log::info('SujetosProcesales: iniciando extracción', [
                'user'     => $request->user()?->id,
                'filename' => $file->getClientOriginalName(),
                'size_mb'  => round($file->getSize() / 1048576, 2),
            ]);

            // 1. Extraer texto del PDF vía OCR
            $texto = $this->ocr->extractFromPdf($absPath);

            if (empty(trim($texto))) {
                return response()->json([
                    'error' => 'No se pudo extraer texto del PDF. Verifique que no esté protegido o en blanco.',
                ], 422);
            }

            // Truncar el texto para no superar la ventana de contexto del modelo
            $textoOriginalChars = strlen($texto);
            if ($textoOriginalChars > self::MAX_TEXT_CHARS) {
                $texto = mb_substr($texto, 0, self::MAX_TEXT_CHARS);
                Log::info('SujetosProcesales: texto truncado', [
                    'original_chars' => $textoOriginalChars,
                    'truncado_chars' => self::MAX_TEXT_CHARS,
                ]);
            }

            Log::info('SujetosProcesales: texto extraído', ['chars' => strlen($texto)]);

            // 2. Construir el prompt con el texto extraído
            $promptFinal = $this->promptService->buildPrompt(
                $request->input('prompt_id'),
                $texto
            );

            // 3. Enviar al modelo LLM con timeout extendido
            $promptChars = strlen($promptFinal);
            $startTime   = microtime(true);
            $respuesta   = $this->ollama->chat($promptFinal, self::OLLAMA_TIMEOUT);
            $elapsedSecs = round(microtime(true) - $startTime, 2);

            Log::info('SujetosProcesales: respuesta Ollama recibida', [
                'chars'     => strlen($respuesta),
                'elapsed_s' => $elapsedSecs,
            ]);

            // 4. Intentar parsear JSON de la respuesta
            $json = $this->parsearRespuestaJson($respuesta);

            return response()->json([
                'resultado' => $json,
                'crudo'     => $respuesta,
                'stats'     => [
                    'prompt_chars' => $promptChars,
                    'prompt_kb'    => round($promptChars / 1024, 2),
                    'elapsed_s'    => $elapsedSecs,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['error' => 'El prompt seleccionado no existe.'], 422);

        } catch (\RuntimeException $e) {
            Log::error('SujetosProcesales: error de servicio', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);

        } catch (\Exception $e) {
            Log::error('SujetosProcesales: error inesperado', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Ocurrió un error inesperado. Intente nuevamente.'], 500);

        } finally {
            if (isset($absPath) && file_exists($absPath)) {
                unlink($absPath);
            }
        }
    }

    /**
     * Intenta extraer un objeto JSON válido de la respuesta del modelo.
     * Si falla, retorna la respuesta original como texto.
     *
     * @return array|string
     */
    private function parsearRespuestaJson(string $respuesta): array|string
    {
        // Buscar bloque JSON entre llaves
        if (preg_match('/\{.*\}/s', $respuesta, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $respuesta;
    }
}
