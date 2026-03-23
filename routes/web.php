<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OcrExtractorController;
use App\Http\Controllers\PdfAnalyzerController;
use App\Http\Controllers\TranscripcionController;
use Illuminate\Support\Facades\Route;


// ── Dashboard ─────────────────────────────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

Route::get('/ingresados_fuero', [DashboardController::class, 'ingresadosFuero'])
    ->name('ingresados_fuero'); 
    

Route::get('/dashboard/actuaciones', [DashboardController::class, 'actuaciones'])
    ->name('dashboard.actuaciones');

// ── Dashboard V2 ──────────────────────────────────────────────────────────────
Route::get('/v2/dashboard', [DashboardController::class, 'indexV2'])
    ->name('dashboard.v2');

Route::get('/v2/ingresados_fuero', [DashboardController::class, 'ingresadosFueroV2'])
    ->name('ingresados_fuero.v2');

// ── Analizador / Anonimizador de PDF ─────────────────────────────────────────
Route::get('/',           [PdfAnalyzerController::class, 'showForm'])->name('pdf-analyzer.form');
Route::get('/pdf-analyzer',           [PdfAnalyzerController::class, 'showForm'])->name('pdf-analyzer.form');

Route::post('/pdf-analyzer/process',  [PdfAnalyzerController::class, 'processPdf'])->name('pdf-analyzer.process');
Route::post('/pdf-analyzer/anonimize',[PdfAnalyzerController::class, 'anonimizeEntities'])->name('pdf-analyzer.anonimize');
Route::get('/pdf-analyzer/export',    [PdfAnalyzerController::class, 'exportPdf'])->name('pdf-analyzer.export');

// ── Transcriptor Multimedia (Whisper) ─────────────────────────────────────────────
Route::get('/transcripcion',          [TranscripcionController::class, 'index'])->name('transcripcion.index');
Route::post('/transcripcion/procesar', [TranscripcionController::class, 'transcribir'])->name('transcripcion.procesar');

// ── Extractor de Texto via OCR (Tesseract) ────────────────────────────────────────
Route::get('/ocr-extractor',          [OcrExtractorController::class, 'index'])->name('pdf-extractor.index');
Route::post('/ocr-extractor/extract', [OcrExtractorController::class, 'extract'])->name('pdf-extractor.extract');
