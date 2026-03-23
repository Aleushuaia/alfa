<?php

namespace App\Http\Controllers;

use App\Services\OcrExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OcrExtractorController extends Controller
{
    /** @var OcrExtractorService */
    private $ocr;

    public function __construct(OcrExtractorService $ocr)
    {
        $this->ocr = $ocr;
    }

    /**
     * Muestra el formulario de extracción OCR.
     */
    public function index()
    {
        return view('ocr.index');
    }

    /**
     * Recibe el PDF, ejecuta OCR y devuelve el texto extraído como JSON.
     */
    public function extract(Request $request)
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:51200'], // 50 MB
        ], [
            'pdf.required' => 'Debe seleccionar un archivo PDF.',
            'pdf.mimes'    => 'Solo se aceptan archivos PDF.',
            'pdf.max'      => 'El archivo no debe superar los 50 MB.',
        ]);

        $file    = $request->file('pdf');

        // Aseguramos que el directorio existe en el disco local
        Storage::disk('local')->makeDirectory('temp-ocr');

        $tmpPath = $file->store('temp-ocr', 'local');
        // Usamos path() del disco para obtener la ruta absoluta real
        $absPath = Storage::disk('local')->path($tmpPath);

        try {
            Log::info('OCR: iniciando extracción', [
                'filename' => $file->getClientOriginalName(),
                'size_mb'  => round($file->getSize() / 1048576, 2),
            ]);

            $text = $this->ocr->extractFromPdf($absPath);

            Log::info('OCR: extracción OK', ['chars' => strlen($text)]);

            return response()->json([
                'text'  => $text,
                'chars' => strlen($text),
                'pages' => substr_count($text, "\n\n") + 1,
            ]);

        } catch (\Exception $e) {
            Log::error('OCR: error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);

        } finally {
            if (file_exists($absPath)) {
                unlink($absPath);
            }
        }
    }
}
