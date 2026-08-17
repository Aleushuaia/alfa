<?php

namespace App\Http\Controllers;

use App\Services\OcrExtractorService;
use App\Services\PdfCompressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PdfToolsController extends Controller
{
    public function __construct(
        private OcrExtractorService   $ocr,
        private PdfCompressionService $compressor,
    ) {}

    /** Vista principal con dropzone y herramientas. */
    public function index()
    {
        $levels = PdfCompressionService::LEVELS;
        return view('pdf-tools.index', compact('levels'));
    }

    /** POST /pdf-tools/ocr — extrae texto con Tesseract y devuelve JSON. */
    public function ocr(Request $request): JsonResponse
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ], [
            'pdf.required' => 'Debe seleccionar un archivo PDF.',
            'pdf.mimes'    => 'Solo se aceptan archivos PDF.',
            'pdf.max'      => 'El archivo no debe superar los 50 MB.',
        ]);

        $file    = $request->file('pdf');
        Storage::disk('local')->makeDirectory('temp-ocr');
        $tmpPath = $file->store('temp-ocr', 'local');
        $absPath = Storage::disk('local')->path($tmpPath);

        try {
            Log::info('PdfTools OCR: inicio', [
                'file' => $file->getClientOriginalName(),
                'mb'   => round($file->getSize() / 1048576, 2),
            ]);

            $text = $this->ocr->extractFromPdf($absPath);

            Log::info('PdfTools OCR: OK', ['chars' => strlen($text)]);

            return response()->json([
                'text'  => $text,
                'chars' => strlen($text),
                'pages' => max(1, substr_count($text, "\n\n") + 1),
            ]);
        } catch (\Exception $e) {
            Log::error('PdfTools OCR: error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        } finally {
            if (file_exists($absPath)) {
                unlink($absPath);
            }
        }
    }

    /** POST /pdf-tools/compress — comprime con Ghostscript y devuelve el PDF. */
    public function compress(Request $request)
    {
        $request->validate([
            'pdf'   => ['required', 'file', 'mimes:pdf', 'max:102400'],
            'level' => ['nullable', 'string', 'in:screen,ebook,printer,prepress'],
        ], [
            'pdf.required' => 'Debe seleccionar un archivo PDF.',
            'pdf.mimes'    => 'Solo se aceptan archivos PDF.',
            'pdf.max'      => 'El archivo no debe superar los 100 MB.',
            'level.in'     => 'Nivel de compresión inválido.',
        ]);

        $file  = $request->file('pdf');
        $level = $request->input('level', 'ebook');

        Storage::disk('local')->makeDirectory('temp-compress');

        $inPath  = $file->store('temp-compress', 'local');
        $absIn   = Storage::disk('local')->path($inPath);
        $absOut  = Storage::disk('local')->path('temp-compress/compressed_' . uniqid() . '.pdf');

        $originalSize = $file->getSize();

        try {
            Log::info('PdfTools Compress: inicio', [
                'file'  => $file->getClientOriginalName(),
                'level' => $level,
                'mb'    => round($originalSize / 1048576, 2),
            ]);

            $this->compressor->compress($absIn, $absOut, $level);

            $compressedSize = filesize($absOut);
            $reduction      = $originalSize > 0
                ? round((1 - $compressedSize / $originalSize) * 100, 1)
                : 0;

            Log::info('PdfTools Compress: OK', [
                'original_kb'   => round($originalSize / 1024),
                'compressed_kb' => round($compressedSize / 1024),
                'reduction_pct' => $reduction,
            ]);

            $downloadName = preg_replace('/\.pdf$/i', '', $file->getClientOriginalName())
                . '_' . $level . '_comprimido.pdf';

            return response()->download($absOut, $downloadName, [
                'Content-Type'              => 'application/pdf',
                'X-Original-Size'           => $originalSize,
                'X-Compressed-Size'         => $compressedSize,
                'X-Reduction-Percent'       => $reduction,
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            if (file_exists($absOut)) {
                unlink($absOut);
            }
            Log::error('PdfTools Compress: error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        } finally {
            if (file_exists($absIn)) {
                unlink($absIn);
            }
        }
    }
}
