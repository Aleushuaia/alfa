<?php

use App\Http\Controllers\AdministradorUnidadController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntityConfigController;
use App\Http\Controllers\GestionarUnidadController;
use App\Http\Controllers\OcrExtractorController;
use App\Http\Controllers\PdfAnalyzerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SwitchUnidadController;
use App\Http\Controllers\TranscripcionController;
use App\Http\Controllers\ThemeConfigController;
use App\Http\Controllers\UnidadController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WordAnonymizerController;
use Illuminate\Support\Facades\Route;

// ── Autenticación ─────────────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── Redirect raíz al login o al home ─────────────────────────────────────────
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('pdf-analyzer.form')
        : redirect()->route('login');
})->name('home');

// ══════════════════════════════════════════════════════════════════════════════
// Rutas protegidas — requieren autenticación
// ══════════════════════════════════════════════════════════════════════════════
Route::middleware('auth')->group(function () {

    // ── Cambiar unidad activa (sesión) ────────────────────────────────────
    Route::post('/switch-unidad', SwitchUnidadController::class)->name('switch-unidad');

    // ── Perfil: cambio de contraseña propio ──────────────────────────────
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');

    // ── Gestionar Unidad (administradores de unidad, sin rol global) ─────
    Route::get('/gestionar-unidad',                           [GestionarUnidadController::class, 'index'])->name('gestionar-unidad.index');
    Route::get('/gestionar-unidad/{unidad}',                  [GestionarUnidadController::class, 'show'])->name('gestionar-unidad.show');
    Route::post('/gestionar-unidad/{unidad}/users',           [GestionarUnidadController::class, 'attachUser'])->name('gestionar-unidad.attach-user');
    Route::delete('/gestionar-unidad/{unidad}/users/{user}',  [GestionarUnidadController::class, 'detachUser'])->name('gestionar-unidad.detach-user');

    // ── Dashboard ─────────────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/ingresados_fuero', [DashboardController::class, 'ingresadosFuero'])
        ->name('ingresados_fuero');

    Route::get('/dashboard/actuaciones', [DashboardController::class, 'actuaciones'])
        ->name('dashboard.actuaciones');

    // ── Dashboard V2 ──────────────────────────────────────────────────────
    Route::get('/v2/dashboard', [DashboardController::class, 'indexV2'])
        ->name('dashboard.v2');

    Route::get('/v2/ingresados_fuero', [DashboardController::class, 'ingresadosFueroV2'])
        ->name('ingresados_fuero.v2');

    // ── Analizador / Anonimizador de PDF ─────────────────────────────────
    Route::get('/pdf-analyzer', [PdfAnalyzerController::class, 'showForm'])->name('pdf-analyzer.form');
    Route::post('/pdf-analyzer/process', [PdfAnalyzerController::class, 'processPdf'])->name('pdf-analyzer.process');
    Route::post('/pdf-analyzer/anonimize', [PdfAnalyzerController::class, 'anonimizeEntities'])->name('pdf-analyzer.anonimize');
    Route::post('/pdf-analyzer/analyze-text', [PdfAnalyzerController::class, 'analyzeText'])->name('pdf-analyzer.analyze-text');
    Route::post('/pdf-analyzer/blacklist', [PdfAnalyzerController::class, 'addToBlacklist'])->name('pdf-analyzer.add-blacklist');
    Route::get('/pdf-analyzer/export', [PdfAnalyzerController::class, 'exportPdf'])->name('pdf-analyzer.export');

    // ── Gestión de Blacklist ───────────────────────────────────────────────
    Route::get('/blacklist', [PdfAnalyzerController::class, 'blacklistIndex'])->name('blacklist.index');
    Route::delete('/blacklist/{id}', [PdfAnalyzerController::class, 'blacklistDelete'])->name('blacklist.delete');

    // ── Gestión de Whitelist ──────────────────────────────────────────────
    Route::post('/pdf-analyzer/whitelist', [PdfAnalyzerController::class, 'addToWhitelist'])->name('pdf-analyzer.add-whitelist');
    Route::get('/whitelist', [PdfAnalyzerController::class, 'whitelistIndex'])->name('whitelist.index');
    Route::delete('/whitelist/{id}', [PdfAnalyzerController::class, 'whitelistDelete'])->name('whitelist.delete');

    // ── Transcriptor Multimedia (Whisper) ─────────────────────────────────
    Route::get('/transcripcion', [TranscripcionController::class, 'index'])->name('transcripcion.index');
    Route::post('/transcripcion/procesar', [TranscripcionController::class, 'transcribir'])->name('transcripcion.procesar');

    // ── Extractor de Texto via OCR (Tesseract) ───────────────────────────
    Route::get('/ocr-extractor', [OcrExtractorController::class, 'index'])->name('pdf-extractor.index');
    Route::post('/ocr-extractor/extract', [OcrExtractorController::class, 'extract'])->name('pdf-extractor.extract');

    // ── Anonimizador de Word ──────────────────────────────────────────────
    Route::get('/word-anonymizer', [WordAnonymizerController::class, 'index'])->name('word-anonymizer.index');
    Route::post('/word-anonymizer/process', [WordAnonymizerController::class, 'process'])->name('word-anonymizer.process');    Route::post('/word-anonymizer/analyze', [WordAnonymizerController::class, 'analyzeText'])->name('word-anonymizer.analyze');
    Route::post('/word-anonymizer/anonymize', [WordAnonymizerController::class, 'anonymize'])->name('word-anonymizer.anonymize');
    Route::get('/word-anonymizer/download', [WordAnonymizerController::class, 'download'])->name('word-anonymizer.download');
    // ── Gestión de entidades (colores por usuario) ──────────────────────
    Route::get('/entity-config', [EntityConfigController::class, 'index'])->name('entity-config.index');
    Route::post('/entity-config', [EntityConfigController::class, 'save'])->name('entity-config.save');

    // ── Colores del tema (por usuario) ──────────────────────────────────
    Route::get('/theme-config', [ThemeConfigController::class, 'index'])->name('theme-config.index');
    Route::post('/theme-config', [ThemeConfigController::class, 'save'])->name('theme-config.save');
    Route::get('/theme-config/reset', [ThemeConfigController::class, 'reset'])->name('theme-config.reset');
    Route::get('/api/user-theme-colors', [ThemeConfigController::class, 'getUserColors'])->name('api.user-theme-colors');

    // ── Ajustes (solo administrador) ─────────────────────────────────────
    Route::middleware('role:administrador')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::put('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

        // ── Unidades de Trabajo ──────────────────────────────────────────
        Route::resource('unidades', UnidadController::class)
            ->parameters(['unidades' => 'unidad']);
        Route::post('/unidades/{unidad}/users',          [UnidadController::class, 'attachUser'])->name('unidades.attach-user');
        Route::delete('/unidades/{unidad}/users/{user}', [UnidadController::class, 'detachUser'])->name('unidades.detach-user');

        // ── Administradores de Unidades ──────────────────────────────────
        Route::get('/administradores-unidades',                        [AdministradorUnidadController::class, 'index'])->name('administradores-unidades.index');
        Route::post('/administradores-unidades/attach',                [AdministradorUnidadController::class, 'attach'])->name('administradores-unidades.attach');
        Route::delete('/administradores-unidades/{unidad}/{user}',     [AdministradorUnidadController::class, 'detach'])->name('administradores-unidades.detach');
    });
});
