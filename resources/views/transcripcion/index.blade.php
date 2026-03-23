@extends('layouts.dashboard')

@section('title', 'Transcriptor Multimedia — Pento')
@section('page-title', 'Transcriptor Multimedia')
@section('breadcrumb', 'Transcriptor')

@push('styles')
<style>
/* ── Contenedor principal ─────────────────────────────────────────────────── */
.transcriptor-wrap {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    align-items: start;
}
@media (max-width: 900px) {
    .transcriptor-wrap { grid-template-columns: 1fr; }
}

/* ── Tarjeta base (misma estética que el dashboard) ─────────────────────── */
.t-card {
    background: #fff;
    border-radius: var(--card-radius, 14px);
    box-shadow: var(--card-shadow, 0 2px 20px rgba(0,0,0,.07));
    padding: 1.75rem;
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
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(99,102,241,.35);
}
.t-card-header h5 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
}
.t-card-header p {
    margin: 0;
    font-size: .78rem;
    color: #64748b;
}

/* ── Zona de drop ────────────────────────────────────────────────────────── */
.drop-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 2.5rem 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: border-color .25s, background .25s;
    background: #f8fafc;
    position: relative;
}
.drop-zone:hover,
.drop-zone.drag-over {
    border-color: #6366f1;
    background: #eef2ff;
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
    color: #6366f1;
    margin-bottom: .75rem;
    display: block;
}
.drop-zone .dz-title {
    font-size: .95rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: .3rem;
}
.drop-zone .dz-sub {
    font-size: .8rem;
    color: #94a3b8;
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
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
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
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 10px;
    padding: .85rem 1rem;
    margin-top: 1rem;
}
.file-preview.show { display: flex; }
.file-preview .fp-icon {
    font-size: 1.6rem;
    color: #0284c7;
    flex-shrink: 0;
}
.file-preview .fp-info { flex: 1; min-width: 0; }
.file-preview .fp-name {
    font-size: .875rem;
    font-weight: 600;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.file-preview .fp-size { font-size: .75rem; color: #64748b; }
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
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border: none;
    border-radius: 10px;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    transition: opacity .2s, transform .15s;
    box-shadow: 0 4px 14px rgba(99,102,241,.35);
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
    color: #64748b;
    margin-bottom: .35rem;
    display: flex;
    justify-content: space-between;
}
.t-progress-bar {
    height: 6px;
    border-radius: 99px;
    background: #e2e8f0;
    overflow: hidden;
}
.t-progress-bar-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #6366f1, #8b5cf6);
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
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: .72rem;
    font-weight: 600;
    color: #475569;
    padding: .2rem .6rem;
    display: inline-flex;
    align-items: center;
    gap: .3rem;
}
.t-meta-chip i { color: #6366f1; }

.t-textarea {
    width: 100%;
    min-height: 340px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 1rem;
    font-family: 'Inter', sans-serif;
    font-size: .875rem;
    color: #1e293b;
    line-height: 1.65;
    resize: vertical;
    transition: border-color .2s;
    outline: none;
}
.t-textarea:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
.t-textarea::placeholder { color: #cbd5e1; }

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
.t-btn-copy   { background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }
.t-btn-download { background: linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; box-shadow: 0 3px 10px rgba(99,102,241,.3); }
.t-btn-clear  { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; margin-left: auto; }

/* ── Estado vacío ────────────────────────────────────────────────────────── */
.t-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    color: #94a3b8;
    text-align: center;
    gap: .75rem;
}
.t-empty-state i { font-size: 2.5rem; opacity: .35; }
.t-empty-state p { margin: 0; font-size: .85rem; }

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
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 8px 30px rgba(0,0,0,.12);
    padding: .85rem 1.1rem;
    display: flex;
    align-items: center;
    gap: .75rem;
    font-size: .84rem;
    font-weight: 500;
    color: #1e293b;
    border-left: 4px solid #6366f1;
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

        <form id="transcriptor-form" novalidate>
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

        {{-- Info adicional --}}
        <div class="mt-3 pt-3" style="border-top:1px solid #f1f5f9;">
            <p class="mb-0" style="font-size:.75rem;color:#94a3b8;">
                <i class="fas fa-info-circle me-1" style="color:#6366f1;"></i>
                El procesamiento con Whisper puede demorar según el tamaño del archivo
                y los recursos disponibles. Tamaño máximo: <strong>200 MB</strong>.
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
        <div class="t-result-actions" id="result-actions" style="display:none;">
            <button type="button" class="t-btn t-btn-copy" id="btn-copy">
                <i class="fas fa-copy"></i> Copiar
            </button>
            <button type="button" class="t-btn t-btn-download" id="btn-download">
                <i class="fas fa-file-arrow-down"></i> Descargar .txt
            </button>
            <button type="button" class="t-btn t-btn-clear" id="btn-clear-result">
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
        resultActions.style.display = 'flex';
        textarea.value = data.text;

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
        resultActions.style.display = 'none';
        resultMeta.style.display = 'none!important';
        resultMeta.innerHTML = '';
        textarea.value = '';
        emptyState.style.display = 'flex';
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

})();
</script>
@endpush
