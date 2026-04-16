@extends('layouts.dashboard')

@section('title', 'Transcripciones — Alfa')
@section('page-title', 'Transcripciones')
@section('breadcrumb', 'Transcripciones')

@push('styles')
<style>
/* ── Contenedor principal ─────────────────────────────────────────────────── */
.transcriptor-wrap {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    align-items: stretch;
}
.transcriptor-wrap > .t-card {
    display: flex;
    flex-direction: column;
    min-height: 80vh;
}
@media (max-width: 900px) {
    .transcriptor-wrap { grid-template-columns: 1fr; }
}

/* ── Tarjeta base (misma estética que el dashboard) ─────────────────────── */
.t-card {
    background: var(--card-bg);
    border-radius: var(--card-radius, 14px);
    box-shadow: var(--card-shadow, 0 2px 20px rgba(0,0,0,.07));
    border: 1px solid var(--card-border);
    padding: 1.75rem;
    color: var(--body-color);
}
.t-card-header {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.5rem;
}
.t-card-header .t-card-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 14px var(--accent-glow, rgba(59,130,246,.35));
}
.t-card-header h5 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--heading-color);
}
.t-card-header p {
    margin: 0;
    font-size: .78rem;
    color: var(--muted-color);
}

/* ── Zona de drop ────────────────────────────────────────────────────────── */
.drop-zone {
    border: 2px dashed var(--input-border);
    border-radius: 12px;
    padding: 2.5rem 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: border-color .25s, background .25s;
    background: var(--input-bg);
    position: relative;
}
.drop-zone:hover,
.drop-zone.drag-over {
    border-color: var(--accent);
    background: color-mix(in srgb, var(--accent) 6%, var(--input-bg));
}
.drop-zone input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}
.drop-zone .dz-icon {
    font-size: 2.5rem;
    color: var(--accent);
    margin-bottom: .75rem;
    display: block;
}
.drop-zone .dz-title {
    font-size: .95rem;
    font-weight: 600;
    color: var(--heading-color);
    margin-bottom: .3rem;
}
.drop-zone .dz-sub {
    font-size: .8rem;
    color: var(--muted-color);
}

/* ── Badge de formato ──────────────────────────────────────────────────── */
.format-badges {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    margin-top: 1rem;
    justify-content: center;
}
.format-badge {
    background: var(--badge-light-bg);
    color: var(--badge-light-color);
    border: 1px solid var(--badge-light-border);
    border-radius: 6px;
    font-size: .7rem;
    font-weight: 600;
    padding: .2rem .5rem;
    letter-spacing: .04em;
}

/* ── Preview del archivo seleccionado ────────────────────────────────────── */
.file-preview {
    display: none;
    align-items: center;
    gap: .85rem;
    background: var(--badge-light-bg);
    border: 1px solid var(--badge-light-border);
    border-radius: 10px;
    padding: .85rem 1rem;
    margin-top: 1rem;
}
.file-preview.show { display: flex; }
.file-preview .fp-icon {
    font-size: 1.6rem;
    color: var(--accent);
    flex-shrink: 0;
}
.file-preview .fp-info { flex: 1; min-width: 0; }
.file-preview .fp-name {
    font-size: .875rem;
    font-weight: 600;
    color: var(--heading-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.file-preview .fp-size { font-size: .75rem; color: var(--muted-color); }
.file-preview .fp-remove {
    background: none; border: none;
    color: #94a3b8; font-size: 1.1rem;
    cursor: pointer; padding: 0 .25rem;
    flex-shrink: 0;
    transition: color .2s;
}
.file-preview .fp-remove:hover { color: #ef4444; }

/* ── Botón enviar ────────────────────────────────────────────────────────── */
.btn-transcribir {
    width: 100%;
    margin-top: 1.25rem;
    padding: .75rem 1rem;
    font-size: .9rem;
    font-weight: 600;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border: none;
    border-radius: 10px;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    transition: opacity .2s, transform .15s;
    box-shadow: 0 4px 14px var(--accent-glow, rgba(59,130,246,.35));
}
.btn-transcribir:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
.btn-transcribir:disabled { opacity: .55; cursor: not-allowed; }

/* ── Barra de progreso ───────────────────────────────────────────────────── */
.t-progress-wrap {
    display: none;
    margin-top: 1rem;
}
.t-progress-wrap.show { display: block; }
.t-progress-label {
    font-size: .78rem;
    color: var(--muted-color, #64748b);
    margin-bottom: .35rem;
    display: flex;
    justify-content: space-between;
}
.t-progress-bar {
    height: 6px;
    border-radius: 99px;
    background: var(--badge-light-bg, #e2e8f0);
    overflow: hidden;
}
.t-progress-bar-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, var(--accent), var(--accent2));
    border-radius: 99px;
    transition: width .4s ease;
    animation: pulsebar 1.5s ease-in-out infinite;
}
@keyframes pulsebar {
    0%,100% { opacity:1; } 50% { opacity:.6; }
}

/* ── Resultado ───────────────────────────────────────────────────────────── */
.t-result-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-bottom: 1rem;
}
.t-meta-chip {
    background: var(--badge-light-bg, #f1f5f9);
    border: 1px solid var(--badge-light-border, #e2e8f0);
    border-radius: 6px;
    font-size: .72rem;
    font-weight: 600;
    color: var(--badge-light-color, #475569);
    padding: .2rem .6rem;
    display: inline-flex;
    align-items: center;
    gap: .3rem;
}
.t-meta-chip i { color: var(--accent); }

.t-textarea {
    width: 100%;
    min-height: 340px;
    flex: 1;
    border: 1.5px solid var(--input-border, #e2e8f0);
    border-radius: 10px;
    padding: 1rem;
    font-family: 'Inter', sans-serif;
    font-size: .875rem;
    color: var(--input-color, #1e293b);
    line-height: 1.65;
    resize: vertical;
    transition: border-color .2s;
    outline: none;
    background: var(--input-bg, #fff);
}
.t-textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow, rgba(59,130,246,.12)); background: var(--input-bg, #fff); }
.t-textarea::placeholder { color: var(--input-placeholder, #cbd5e1); }

.t-result-actions {
    display: flex;
    gap: .75rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}
.t-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .55rem 1.1rem;
    border-radius: 8px;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: opacity .2s, transform .15s;
}
.t-btn:hover { opacity: .88; transform: translateY(-1px); }
.t-btn:disabled { opacity: .5; cursor: not-allowed; }
.t-btn-copy   { background: var(--badge-light-bg, #f1f5f9); color: var(--badge-light-color, #334155); border: 1px solid var(--badge-light-border, #e2e8f0); }
.t-btn-download { background: linear-gradient(135deg, var(--accent), var(--accent2)); color:#fff; box-shadow: 0 3px 10px var(--accent-glow, rgba(59,130,246,.3)); }
.t-btn-clear  { background: var(--alert-danger-bg, #fef2f2); color: var(--alert-danger-color, #ef4444); border: 1px solid rgba(239,68,68,.2); margin-left: auto; }

/* ── Estado vacío ────────────────────────────────────────────────────────── */
.t-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    color: var(--muted-color, #94a3b8);
    text-align: center;
    gap: .75rem;
    flex: 1;
}
.t-empty-state i { font-size: 2.5rem; opacity: .35; }
.t-empty-state p { margin: 0; font-size: .85rem; }

/* ── Micrófono: separador ────────────────────────────────────────────────── */
.mic-divider {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin: 1.5rem 0 1rem;
}
.mic-divider-line {
    flex: 1;
    height: 1px;
    background: var(--border, #e2e8f0);
}
.mic-divider-text {
    font-size: .78rem;
    font-weight: 600;
    color: var(--muted, #94a3b8);
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: .04em;
}

/* ── Micrófono: sección ──────────────────────────────────────────────────── */
.mic-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .75rem;
    padding: 1.25rem 0 .5rem;
}

.mic-btn {
    position: relative;
    width: 68px;
    height: 68px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff;
    font-size: 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform .2s, box-shadow .2s, background .3s;
    box-shadow: 0 4px 18px var(--accent-glow, rgba(59,130,246,.35));
    z-index: 1;
}
.mic-btn:hover:not(.recording) {
    transform: scale(1.08);
    box-shadow: 0 6px 24px var(--accent-glow, rgba(59,130,246,.45));
}
.mic-btn:active { transform: scale(.96); }

/* Estado grabando */
.mic-btn.recording {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    box-shadow: 0 4px 20px rgba(239,68,68,.45);
    animation: mic-breathe 1.5s ease-in-out infinite;
}
.mic-btn.recording i::before { content: "\f04d"; } /* fa-stop */
@keyframes mic-breathe {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.06); }
}

/* Pulso animado detrás del botón */
.mic-pulse {
    position: absolute;
    inset: -6px;
    border-radius: 50%;
    border: 2px solid var(--accent-glow, rgba(59,130,246,.4));
    opacity: 0;
    pointer-events: none;
}
.mic-btn.recording .mic-pulse {
    animation: mic-ring 1.4s ease-out infinite;
    border-color: rgba(239,68,68,.5);
}
@keyframes mic-ring {
    0%   { transform: scale(1); opacity: .7; }
    100% { transform: scale(1.6); opacity: 0; }
}

/* Info y timer */
.mic-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .2rem;
}
.mic-status {
    font-size: .8rem;
    font-weight: 500;
    color: var(--muted, #94a3b8);
    transition: color .3s;
}
.mic-status.active {
    color: #ef4444;
    font-weight: 600;
}
.mic-timer {
    font-size: 1.15rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: #ef4444;
    letter-spacing: .02em;
}

/* ── Streaming en tiempo real ─────────────────────────────────────────── */
.rt-streaming-badge {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .25rem .7rem;
    border-radius: 99px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .03em;
    text-transform: uppercase;
    background: rgba(239,68,68,.1);
    color: #ef4444;
    border: 1px solid rgba(239,68,68,.2);
    animation: rt-blink 1.2s ease-in-out infinite;
}
.rt-streaming-badge .rt-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #ef4444;
    animation: rt-pulse 1s ease-in-out infinite;
}
@keyframes rt-blink {
    0%,100% { opacity: 1; }
    50%     { opacity: .6; }
}
@keyframes rt-pulse {
    0%,100% { transform: scale(1); }
    50%     { transform: scale(1.4); }
}

.rt-interim {
    color: var(--muted, #94a3b8);
    font-style: italic;
}
.rt-final {
    color: var(--fg, #1e293b);
}

.rt-refining {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .5rem .8rem;
    margin-top: .5rem;
    border-radius: 8px;
    font-size: .78rem;
    font-weight: 600;
    color: var(--accent);
    background: var(--accent-glow, rgba(59,130,246,.08));
    border: 1px solid color-mix(in srgb, var(--accent) 15%, transparent);
}
.rt-refining i { animation: fa-spin 1s linear infinite; }

/* ── Toast ───────────────────────────────────────────────────────────────── */
.t-toast-container {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: .6rem;
}
.t-toast {
    background: var(--card-bg, #fff);
    border-radius: 10px;
    box-shadow: var(--card-shadow, 0 8px 30px rgba(0,0,0,.12));
    padding: .85rem 1.1rem;
    display: flex;
    align-items: center;
    gap: .75rem;
    font-size: .84rem;
    font-weight: 500;
    color: var(--body-color, #1e293b);
    border-left: 4px solid var(--accent);
    animation: slideInRight .3s ease;
    min-width: 260px;
    max-width: 360px;
}
.t-toast.toast-error   { border-left-color: #ef4444; }
.t-toast.toast-success { border-left-color: #22c55e; }
.t-toast i { font-size: 1.1rem; }
.t-toast.toast-error   i { color: #ef4444; }
.t-toast.toast-success i { color: #22c55e; }
@keyframes slideInRight {
    from { opacity:0; transform: translateX(30px); }
    to   { opacity:1; transform: translateX(0); }
}
</style>
@endpush

@section('content')
<div class="transcriptor-wrap">

    {{-- ── Panel izquierdo: subida de archivo ───────────────────────────── --}}
    <div class="t-card">
        <div class="t-card-header">
            <div class="t-card-icon"><i class="fas fa-microphone-alt"></i></div>
            <div>
                <h5>Transcriptor Multimedia</h5>
                <p>Cargá un audio o video para extraer el texto</p>
            </div>
        </div>

        <form id="transcriptor-form" novalidate style="display:flex;flex-direction:column;flex:1;">
            @csrf

            {{-- Drop zone --}}
            <div class="drop-zone" id="drop-zone">
                <input type="file"
                       id="media-input"
                       name="media"
                       accept=".mp3,.wav,.mp4,.mkv,.ogg,.webm,.m4a">
                <i class="fas fa-cloud-upload-alt dz-icon"></i>
                <div class="dz-title">Arrastrá tu archivo aquí</div>
                <div class="dz-sub">o hacé clic para explorar</div>
                <div class="format-badges">
                    @foreach(['MP3','WAV','MP4','MKV','OGG','WEBM','M4A'] as $fmt)
                        <span class="format-badge">{{ $fmt }}</span>
                    @endforeach
                </div>
            </div>

            {{-- Preview --}}
            <div class="file-preview" id="file-preview">
                <i class="fp-icon fas fa-file-audio" id="file-icon"></i>
                <div class="fp-info">
                    <div class="fp-name" id="file-name">—</div>
                    <div class="fp-size" id="file-size">—</div>
                </div>
                <button type="button" class="fp-remove" id="btn-remove-file" title="Quitar archivo">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>

            {{-- Barra de progreso --}}
            <div class="t-progress-wrap" id="progress-wrap">
                <div class="t-progress-label">
                    <span id="progress-label-text">Procesando…</span>
                    <span id="progress-pct"></span>
                </div>
                <div class="t-progress-bar">
                    <div class="t-progress-bar-fill" id="progress-fill"></div>
                </div>
            </div>

            {{-- Botón --}}
            <button type="submit" class="btn-transcribir" id="btn-transcribir" disabled>
                <i class="fas fa-waveform-lines"></i>
                Transcribir
            </button>
        </form>

        {{-- ── Separador ─────────────────────────────────────────────────── --}}
        <div class="mic-divider">
            <span class="mic-divider-line"></span>
            <span class="mic-divider-text">o dictá con tu voz</span>
            <span class="mic-divider-line"></span>
        </div>

        {{-- ── Grabación con micrófono ─────────────────────────────────── --}}
        <div class="mic-section" id="mic-section">
            <button type="button" class="mic-btn" id="btn-mic" title="Iniciar dictado por voz en tiempo real">
                <i class="fas fa-microphone"></i>
                <span class="mic-pulse"></span>
            </button>
            <div class="mic-info" id="mic-info">
                <span class="mic-status" id="mic-status">Hacé clic para dictar en tiempo real</span>
                <span class="mic-timer" id="mic-timer" style="display:none;">00:00</span>
            </div>
        </div>

        {{-- Info adicional --}}
        <div class="mt-3 pt-3" style="border-top:1px solid var(--card-border,#f1f5f9);">
            <p class="mb-0" style="font-size:.75rem;color:var(--muted-color);">
                <i class="fas fa-info-circle me-1" style="color:var(--accent);"></i>
                El dictado muestra texto en tiempo real mientras hablás. Al detener,
                <strong>Whisper</strong> refina automáticamente la transcripción con mayor precisión.
            </p>
        </div>
    </div>

    {{-- ── Panel derecho: resultado ─────────────────────────────────────── --}}
    <div class="t-card">
        <div class="t-card-header">
            <div class="t-card-icon" style="background:linear-gradient(135deg,#059669,#10b981);">
                <i class="fas fa-file-lines"></i>
            </div>
            <div>
                <h5>Texto extraído</h5>
                <p>Podés editar el resultado antes de copiarlo o descargarlo</p>
            </div>
        </div>

        {{-- Metadatos --}}
        <div class="t-result-meta" id="result-meta" style="display:none!important;"></div>

        {{-- Estado vacío (placeholder) --}}
        <div class="t-empty-state" id="empty-state">
            <i class="fas fa-file-dashed-line"></i>
            <p>La transcripción aparecerá aquí<br>una vez procesado el archivo.</p>
        </div>

        {{-- Textarea editable --}}
        <textarea
            class="t-textarea"
            id="result-textarea"
            placeholder="El texto transcripto aparecerá aquí…"
            style="display:none;"
        ></textarea>

        {{-- Acciones --}}
        <div class="t-result-actions" id="result-actions">
            <button type="button" class="t-btn t-btn-copy" id="btn-copy" disabled>
                <i class="fas fa-copy"></i> Copiar
            </button>
            <button type="button" class="t-btn t-btn-download" id="btn-download" disabled>
                <i class="fas fa-file-arrow-down"></i> Descargar como TXT
            </button>
            <button type="button" class="t-btn t-btn-clear" id="btn-clear-result" disabled>
                <i class="fas fa-trash-alt"></i> Limpiar
            </button>
        </div>
    </div>

</div>

{{-- Toast container --}}
<div class="t-toast-container" id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Referencias DOM ────────────────────────────────────────────────────
    const form          = document.getElementById('transcriptor-form');
    const input         = document.getElementById('media-input');
    const dropZone      = document.getElementById('drop-zone');
    const filePreview   = document.getElementById('file-preview');
    const fileIcon      = document.getElementById('file-icon');
    const fileName      = document.getElementById('file-name');
    const fileSize      = document.getElementById('file-size');
    const btnRemove     = document.getElementById('btn-remove-file');
    const btnSubmit     = document.getElementById('btn-transcribir');
    const progressWrap  = document.getElementById('progress-wrap');
    const progressFill  = document.getElementById('progress-fill');
    const progressLabel = document.getElementById('progress-label-text');
    const resultMeta    = document.getElementById('result-meta');
    const emptyState    = document.getElementById('empty-state');
    const textarea      = document.getElementById('result-textarea');
    const resultActions = document.getElementById('result-actions');
    const btnCopy       = document.getElementById('btn-copy');
    const btnDownload   = document.getElementById('btn-download');
    const btnClear      = document.getElementById('btn-clear-result');
    const toastCont     = document.getElementById('toast-container');

    // ── Helpers ────────────────────────────────────────────────────────────
    const videoExts = ['.mp4', '.mkv', '.webm'];

    function formatBytes(bytes) {
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function extOf(name) {
        return name.slice(name.lastIndexOf('.')).toLowerCase();
    }

    function toast(msg, type = 'info') {
        const icons = { success: 'fa-circle-check', error: 'fa-circle-exclamation', info: 'fa-circle-info' };
        const el = document.createElement('div');
        el.className = `t-toast toast-${type}`;
        el.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i><span>${msg}</span>`;
        toastCont.appendChild(el);
        setTimeout(() => el.remove(), 5000);
    }

    function setFilePreview(file) {
        const ext = extOf(file.name);
        fileIcon.className = 'fp-icon fas ' +
            (videoExts.includes(ext) ? 'fa-file-video' : 'fa-file-audio');
        fileName.textContent = file.name;
        fileSize.textContent = formatBytes(file.size);
        filePreview.classList.add('show');
        btnSubmit.disabled = false;
    }

    function clearFile() {
        input.value = '';
        filePreview.classList.remove('show');
        btnSubmit.disabled = true;
    }

    function showProgress() {
        progressWrap.classList.add('show');
        progressFill.style.width = '0%';
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando…';

        // Animación indeterminada: sube hasta 90% en ~30s y se detiene
        let pct = 0;
        const interval = setInterval(() => {
            if (pct >= 90) { clearInterval(interval); return; }
            pct += (90 - pct) * 0.03;
            progressFill.style.width = pct.toFixed(1) + '%';
        }, 500);
        return interval;
    }

    function hideProgress(interval) {
        clearInterval(interval);
        progressFill.style.width = '100%';
        setTimeout(() => {
            progressWrap.classList.remove('show');
            progressFill.style.width = '0%';
        }, 600);
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="fas fa-waveform-lines"></i> Transcribir';
    }

    function showResult(data) {
        emptyState.style.display = 'none';
        textarea.style.display   = 'block';
        textarea.value = data.text;

        // Enable action buttons
        btnCopy.disabled     = false;
        btnDownload.disabled = false;
        btnClear.disabled    = false;

        // Metadatos
        let metaHtml = '';
        if (data.language)        metaHtml += `<span class="t-meta-chip"><i class="fas fa-globe"></i> Idioma: ${data.language.toUpperCase()}</span>`;
        if (data.duration_seconds) {
            const mins = Math.floor(data.duration_seconds / 60);
            const secs = Math.round(data.duration_seconds % 60);
            metaHtml += `<span class="t-meta-chip"><i class="fas fa-clock"></i> Duración: ${mins}m ${secs}s</span>`;
        }
        if (data.segments_count)  metaHtml += `<span class="t-meta-chip"><i class="fas fa-list"></i> Segmentos: ${data.segments_count}</span>`;
        const wordCount = data.text.trim().split(/\s+/).length;
        metaHtml += `<span class="t-meta-chip"><i class="fas fa-font"></i> Palabras: ${wordCount.toLocaleString()}</span>`;

        if (metaHtml) {
            resultMeta.innerHTML = metaHtml;
            resultMeta.style.removeProperty('display');
        }
    }

    function clearResult() {
        textarea.style.display = 'none';
        resultMeta.style.display = 'none!important';
        resultMeta.innerHTML = '';
        textarea.value = '';
        emptyState.style.display = 'flex';

        // Disable action buttons
        btnCopy.disabled     = true;
        btnDownload.disabled = true;
        btnClear.disabled    = true;
    }

    // ── Drag & Drop ────────────────────────────────────────────────────────
    dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file) {
            // Crear un DataTransfer para asignar al input
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            setFilePreview(file);
        }
    });

    input.addEventListener('change', () => {
        if (input.files.length) setFilePreview(input.files[0]);
    });

    btnRemove.addEventListener('click', clearFile);

    // ── Envío del formulario ───────────────────────────────────────────────
    form.addEventListener('submit', async e => {
        e.preventDefault();
        if (!input.files.length) { toast('Seleccioná un archivo primero.', 'error'); return; }

        const interval = showProgress();
        progressLabel.textContent = 'Enviando archivo al transcriptor…';

        const fd = new FormData();
        fd.append('_token', document.querySelector('input[name="_token"]')?.value
                            ?? document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '');
        fd.append('media', input.files[0]);

        try {
            progressLabel.textContent = 'Transcribiendo con Whisper… (puede tardar varios minutos)';
            const resp = await fetch("{{ route('transcripcion.procesar') }}", {
                method: 'POST',
                body: fd,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            hideProgress(interval);

            // Protección: si la respuesta no es JSON (HTML de error), mostrar mensaje claro
            const contentType = resp.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                const bodyText = await resp.text();
                console.error('Respuesta no-JSON del servidor:', bodyText.substring(0, 500));
                toast('Error del servidor (respuesta inesperada). Revisá la consola para más detalles.', 'error');
                return;
            }

            const data = await resp.json();

            if (!resp.ok || data.error) {
                // Mostrar el primer error de validación si existe
                const msg = data.error
                    ?? (data.errors ? Object.values(data.errors).flat()[0] : null)
                    ?? 'Error desconocido del servidor.';
                toast(msg, 'error');
                return;
            }

            showResult(data);
            toast('¡Transcripción completada con éxito!', 'success');

        } catch (err) {
            hideProgress(interval);
            toast('Error de red: ' + err.message, 'error');
        }
    });

    // ── Acciones de resultado ──────────────────────────────────────────────
    btnCopy.addEventListener('click', async () => {
        if (!textarea.value) return;
        await navigator.clipboard.writeText(textarea.value);
        toast('Texto copiado al portapapeles.', 'success');
    });

    btnDownload.addEventListener('click', () => {
        if (!textarea.value) return;
        const blob = new Blob([textarea.value], { type: 'text/plain;charset=utf-8' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'transcripcion_' + Date.now() + '.txt';
        a.click();
        URL.revokeObjectURL(url);
    });

    btnClear.addEventListener('click', clearResult);

    // ── Grabación con micrófono + Transcripción en tiempo real ─────────────
    // Enfoque híbrido:
    //  • Web Speech API  → texto en tiempo real mientras hablás
    //  • MediaRecorder   → graba audio para enviar a Whisper al final
    //  • Al detener      → Whisper refina la transcripción con calidad superior

    const btnMic    = document.getElementById('btn-mic');
    const micStatus = document.getElementById('mic-status');
    const micTimer  = document.getElementById('mic-timer');

    let mediaRecorder      = null;
    let audioChunks        = [];
    let micTimerInterval   = null;
    let micStartTime       = 0;
    let speechRecognition  = null;
    let rtFinalText        = '';   // texto final acumulado de Speech API
    let rtInterimText      = '';   // texto interim (parcial, cambiando)
    let hasSpeechAPI       = false;

    // Detectar Web Speech API
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        hasSpeechAPI = true;
    }

    function formatTime(seconds) {
        const m = String(Math.floor(seconds / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        return `${m}:${s}`;
    }

    function startMicTimer() {
        micStartTime = 0;
        micTimer.textContent = '00:00';
        micTimer.style.display = 'block';
        micTimerInterval = setInterval(() => {
            micStartTime++;
            micTimer.textContent = formatTime(micStartTime);
        }, 1000);
    }

    function stopMicTimer() {
        clearInterval(micTimerInterval);
        micTimerInterval = null;
    }

    /** Mostrar el panel de resultado en modo streaming en tiempo real */
    function showRealtimePanel() {
        emptyState.style.display = 'none';
        textarea.style.display   = 'block';
        textarea.value           = '';
        textarea.placeholder     = 'Escuchando… el texto aparecerá aquí en tiempo real';
        textarea.readOnly        = true;

        // Mostrar badge de streaming en los metadatos
        resultMeta.innerHTML = '<span class="rt-streaming-badge"><span class="rt-dot"></span> Transcripción en vivo</span>';
        resultMeta.style.removeProperty('display');
    }

    /** Actualizar el textarea con texto real-time (final + interim) */
    function updateRealtimeText() {
        const combined = rtFinalText + rtInterimText;
        textarea.value = combined;
        // Auto-scroll al final
        textarea.scrollTop = textarea.scrollHeight;
    }

    /** Iniciar Web Speech API para streaming en tiempo real */
    function startSpeechRecognition() {
        if (!hasSpeechAPI) return;

        speechRecognition = new SpeechRecognition();
        speechRecognition.continuous     = true;
        speechRecognition.interimResults = true;
        speechRecognition.lang           = 'es-AR';  // español argentino
        speechRecognition.maxAlternatives = 1;

        rtFinalText   = '';
        rtInterimText = '';

        speechRecognition.onresult = (event) => {
            let interim = '';
            let final   = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    final += transcript;
                } else {
                    interim += transcript;
                }
            }

            if (final) {
                rtFinalText += final;
            }
            rtInterimText = interim;
            updateRealtimeText();
        };

        speechRecognition.onerror = (event) => {
            // 'no-speech' es normal, no mostrar error
            if (event.error === 'no-speech' || event.error === 'aborted') return;
            console.warn('SpeechRecognition error:', event.error);
        };

        // Si se detiene por inactividad, reiniciar automáticamente
        speechRecognition.onend = () => {
            // Solo reiniciar si seguimos grabando
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                try { speechRecognition.start(); } catch(e) { /* ya detenido */ }
            }
        };

        try {
            speechRecognition.start();
        } catch (e) {
            console.warn('No se pudo iniciar SpeechRecognition:', e);
        }
    }

    function stopSpeechRecognition() {
        if (speechRecognition) {
            try { speechRecognition.abort(); } catch(e) {}
            speechRecognition = null;
        }
    }

    async function startRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

            // Prefer webm (opus) which Whisper handles well
            const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
                ? 'audio/webm;codecs=opus'
                : MediaRecorder.isTypeSupported('audio/webm')
                    ? 'audio/webm'
                    : '';

            mediaRecorder = new MediaRecorder(stream, mimeType ? { mimeType } : {});
            audioChunks = [];

            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) audioChunks.push(e.data);
            };

            mediaRecorder.onstop = async () => {
                // Liberar micrófono
                stream.getTracks().forEach(t => t.stop());
                stopSpeechRecognition();

                const blob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                audioChunks = [];

                if (blob.size < 1000) {
                    toast('La grabación fue muy corta, intentá de nuevo.', 'error');
                    return;
                }

                // Guardar el texto de Speech API como referencia
                const speechText = (rtFinalText + rtInterimText).trim();

                // Enviar a Whisper para transcripción de alta calidad
                await sendAudioToWhisper(blob, speechText);
            };

            mediaRecorder.start(250);

            // UI: estado grabando
            btnMic.classList.add('recording');
            micStatus.textContent = hasSpeechAPI
                ? 'Grabando + transcribiendo en vivo… clic para detener'
                : 'Grabando… clic para detener';
            micStatus.classList.add('active');
            startMicTimer();

            // Activar panel de resultado en modo streaming
            showRealtimePanel();

            // Iniciar transcripción en tiempo real con Speech API
            startSpeechRecognition();

            // Habilitar botones de acción
            btnCopy.disabled     = false;
            btnDownload.disabled = false;
            btnClear.disabled    = false;

        } catch (err) {
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                toast('Permiso de micrófono denegado. Habilitalo en tu navegador.', 'error');
            } else if (err.name === 'NotFoundError') {
                toast('No se encontró ningún micrófono conectado.', 'error');
            } else {
                toast('Error al acceder al micrófono: ' + err.message, 'error');
            }
        }
    }

    function stopRecording() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        btnMic.classList.remove('recording');
        micStatus.textContent = 'Hacé clic para grabar';
        micStatus.classList.remove('active');
        stopMicTimer();
    }

    async function sendAudioToWhisper(blob, speechApiText) {
        const ext = blob.type.includes('webm') ? '.webm'
                  : blob.type.includes('ogg')  ? '.ogg'
                  : blob.type.includes('wav')  ? '.wav'
                  : '.webm';

        const file = new File([blob], 'grabacion_mic' + ext, { type: blob.type });
        const fd   = new FormData();
        fd.append('_token', document.querySelector('input[name="_token"]')?.value ?? '');
        fd.append('media', file);

        // Mostrar indicador de refinamiento con Whisper
        textarea.readOnly = true;
        resultMeta.innerHTML = '<span class="rt-refining"><i class="fas fa-spinner"></i> Refinando con Whisper para mayor precisión…</span>';

        micStatus.textContent = 'Refinando transcripción con Whisper…';

        try {
            const resp = await fetch("{{ route('transcripcion.procesar') }}", {
                method: 'POST',
                body: fd,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const contentType = resp.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                const bodyText = await resp.text();
                console.error('Respuesta no-JSON del servidor:', bodyText.substring(0, 500));

                // Si Whisper falló pero tenemos texto de Speech API, usarlo
                if (speechApiText) {
                    textarea.value    = speechApiText;
                    textarea.readOnly = false;
                    resultMeta.innerHTML = '<span class="t-meta-chip"><i class="fas fa-microphone"></i> Dictado por voz (Speech API)</span>';
                    const wc = speechApiText.split(/\s+/).length;
                    resultMeta.innerHTML += `<span class="t-meta-chip"><i class="fas fa-font"></i> Palabras: ${wc}</span>`;
                    toast('Whisper no respondió; se usó la transcripción en tiempo real.', 'info');
                } else {
                    toast('Error del servidor (respuesta inesperada).', 'error');
                }
                micStatus.textContent = 'Hacé clic para grabar';
                return;
            }

            const data = await resp.json();

            if (!resp.ok || data.error) {
                const msg = data.error
                    ?? (data.errors ? Object.values(data.errors).flat()[0] : null)
                    ?? 'Error desconocido del servidor.';

                // Fallback a Speech API si Whisper falla
                if (speechApiText) {
                    textarea.value    = speechApiText;
                    textarea.readOnly = false;
                    resultMeta.innerHTML = '<span class="t-meta-chip"><i class="fas fa-microphone"></i> Dictado por voz (Speech API)</span>';
                    toast('Whisper falló: ' + msg + '. Se usó transcripción en tiempo real.', 'info');
                } else {
                    toast(msg, 'error');
                }
                micStatus.textContent = 'Hacé clic para grabar';
                return;
            }

            // Éxito con Whisper: reemplazar texto con la versión de mayor calidad
            textarea.readOnly = false;
            showResult(data);

            // Agregar chip indicando que Whisper refinó la transcripción
            const whisperChip = '<span class="t-meta-chip" style="background:var(--accent-glow,rgba(59,130,246,.1));color:var(--accent);"><i class="fas fa-wand-magic-sparkles"></i> Refinado con Whisper</span>';
            resultMeta.innerHTML += whisperChip;

            toast('¡Transcripción en vivo refinada con Whisper!', 'success');
            micStatus.textContent = 'Hacé clic para grabar';

        } catch (err) {
            // Fallback a Speech API si hay error de red
            if (speechApiText) {
                textarea.value    = speechApiText;
                textarea.readOnly = false;
                resultMeta.innerHTML = '<span class="t-meta-chip"><i class="fas fa-microphone"></i> Dictado por voz (Speech API)</span>';
                toast('Error de red con Whisper. Se usó transcripción en tiempo real.', 'info');
            } else {
                toast('Error de red: ' + err.message, 'error');
            }
            micStatus.textContent = 'Hacé clic para grabar';
        }
    }

    // Toggle: clic en el botón del micrófono
    btnMic.addEventListener('click', () => {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            stopRecording();
        } else {
            startRecording();
        }
    });

})();
</script>
@endpush
