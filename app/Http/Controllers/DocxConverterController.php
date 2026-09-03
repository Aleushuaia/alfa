<?php

namespace App\Http\Controllers;

use App\Services\DocxConverterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Convertir a DocX — función aislada de "Procesamiento de Texto".
 *
 * Sube un documento .doc / .rtf / .odt / .docx, lo envía al microservicio
 * `converter_service` (LibreOffice headless), que detecta el formato real por
 * el contenido y lo convierte a un .docx limpio, listo para el Anonimizador.
 */
class DocxConverterController extends Controller
{
    /** Carpeta temporal (disco 'local') para los .docx generados. */
    private const TEMP_DIR = 'temp-convert';

    /** Vida máxima de un .docx generado antes del barrido, en segundos. */
    private const TEMP_TTL = 3600;

    public function __construct(private DocxConverterService $converter) {}

    /** Vista principal con el panel de subida y el panel de resultados. */
    public function index()
    {
        return view('convertir-docx.index');
    }

    /**
     * POST /convertir-docx/convert
     *
     * Respuestas (siempre HTTP 200 salvo error de red/servidor):
     *   { ok: true,  source, result: {status, filename, size_bytes}, warnings, download_url }
     *   { ok: false, source, warnings, message }   ← anomalía detectada en el contenido
     */
    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:51200',
                'extensions:doc,docx,rtf,odt',
            ],
        ], [
            'file.required'   => 'Debe seleccionar un documento.',
            'file.extensions' => 'Solo se aceptan documentos .doc, .docx, .rtf u .odt.',
            'file.max'        => 'El archivo no debe superar los 50 MB.',
        ]);

        $this->sweepOldTempFiles();

        $file = $request->file('file');

        try {
            Log::info('ConvertirDocX: inicio', [
                'filename' => $file->getClientOriginalName(),
                'size_mb'  => round($file->getSize() / 1048576, 2),
            ]);

            $data     = $this->converter->convert($file);
            $source   = $data['source']   ?? [];
            $result   = $data['result']   ?? [];
            $warnings = $data['warnings']  ?? [];
            $message  = $data['message']   ?? '';
            $status   = $result['status'] ?? 'rejected';

            if ($status === 'rejected') {
                Log::info('ConvertirDocX: rechazado', [
                    'detected' => $source['detected_format'] ?? '—',
                    'message'  => $message,
                ]);

                return response()->json([
                    'ok'       => false,
                    'source'   => $source,
                    'warnings' => $warnings,
                    'message'  => $message,
                ]);
            }

            $docx = base64_decode($result['docx_base64'] ?? '', true);
            if ($docx === false || $docx === '') {
                throw new \RuntimeException('El microservicio no devolvió el documento convertido.');
            }

            Storage::disk('local')->makeDirectory(self::TEMP_DIR);
            $token = (string) Str::uuid();
            Storage::disk('local')->put(self::TEMP_DIR . "/{$token}.docx", $docx);

            Log::info('ConvertirDocX: OK', [
                'status'   => $status,
                'detected' => $source['detected_format'] ?? '—',
                'out_kb'   => round(strlen($docx) / 1024),
            ]);

            return response()->json([
                'ok'     => true,
                'source' => $source,
                'result' => [
                    'status'     => $status,
                    'filename'   => $result['filename']   ?? 'documento.docx',
                    'size_bytes' => $result['size_bytes'] ?? strlen($docx),
                ],
                'warnings'     => $warnings,
                'message'      => $message,
                'download_url' => route('convertir-docx.download', $token),
            ]);
        } catch (\Throwable $e) {
            Log::error('ConvertirDocX: error', ['message' => $e->getMessage()]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo convertir el documento: ' . $e->getMessage(),
            ], 422);
        }
    }

    /** GET /convertir-docx/download/{token} — descarga y borra el .docx generado. */
    public function download(string $token)
    {
        if (!preg_match('/^[0-9a-f-]{36}$/i', $token)) {
            abort(404);
        }

        $relPath = self::TEMP_DIR . "/{$token}.docx";
        if (!Storage::disk('local')->exists($relPath)) {
            abort(404, 'El documento convertido ya no está disponible. Volvé a generarlo.');
        }

        return response()->download(
            Storage::disk('local')->path($relPath),
            'documento-convertido.docx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        )->deleteFileAfterSend(true);
    }

    /** Borra .docx temporales anteriores a TEMP_TTL (best-effort). */
    private function sweepOldTempFiles(): void
    {
        try {
            $disk = Storage::disk('local');
            if (!$disk->exists(self::TEMP_DIR)) {
                return;
            }
            foreach ($disk->files(self::TEMP_DIR) as $path) {
                if (time() - $disk->lastModified($path) > self::TEMP_TTL) {
                    $disk->delete($path);
                }
            }
        } catch (\Throwable $e) {
            // Barrido no crítico — ignorar.
        }
    }
}
