@extends('layouts.dashboard')

@section('title', 'Extractor de Texto OCR — ' . config('app.name', 'Pento'))
@section('page-title', 'Extractor de Texto')
@section('breadcrumb', 'OCR')

@push('styles')
<style>
/* ── Contenedor principal ─────────────────────────────────────────────────── */
.ocr-wrap {
    display: grid;
    grid-template-columns: 1fr 3fr;
    gap: 1.5rem;
    align-items: stretch;
}
.ocr-wrap > .t-card {
    min-height: 80vh;
    display: flex;
    flex-direction: column;
}
@media (max-width: 900px) {
    .ocr-wrap { grid-template-columns: 1fr; }
    .ocr-wrap > .t-card { min-height: auto; }
}

/* ── Tarjeta base ─────────────────────────────────────────────────────────── */
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
.t-card-icon {
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

/* ── Drop zone ────────────────────────────────────────────────────────────── */
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

/* ── Badge de formato ─────────────────────────────────────────────────────── */
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

/* ── Preview del archivo ──────────────────────────────────────────────────── */
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

/* ── Botón principal ──────────────────────────────────────────────────────── */
.btn-extraer {
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
.btn-extraer:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
.btn-extraer:disabled { opacity: .55; cursor: not-allowed; }

/* ── Barra de progreso ────────────────────────────────────────────────────── */
.t-progress-wrap {
    display: none;
    margin-top: 1rem;
}
.t-progress-wrap.show { display: block; }
.t-progress-label {
    font-size: .78rem;
    color: var(--muted-color);
    margin-bottom: .35rem;
    display: flex;
    justify-content: space-between;
}
.t-progress-bar {
    height: 6px;
    border-radius: 99px;
    background: var(--badge-light-bg);
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
    0%,100% { opacity:1; }
    50%      { opacity:.6; }
}

/* ── Panel resultado ──────────────────────────────────────────────────────── */
.t-empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--muted-color);
}
.t-empty-state i {
    font-size: 3rem;
    display: block;
    margin-bottom: 1rem;
    color: var(--muted-color);
}
.t-empty-state p {
    font-size: .88rem;
    line-height: 1.6;
    margin: 0;
}

.t-textarea {
    width: 100%;
    min-height: 200px;
    flex: 1;
    border: 1.5px solid var(--input-border);
    border-radius: 10px;
    padding: 1rem;
    font-size: .85rem;
    font-family: 'Inter', sans-serif;
    color: var(--input-color);
    line-height: 1.7;
    resize: vertical;
    background: var(--input-bg);
    transition: border-color .2s;
    outline: none;
    box-sizing: border-box;
}
.t-textarea:focus { border-color: var(--accent); background: var(--input-bg); }

/* ── Metadatos de resultado ───────────────────────────────────────────────── */
.t-result-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
    padding: .6rem .85rem;
    background: var(--alert-success-bg);
    border: 1px solid var(--alert-success-color);
    border-radius: 8px;
    font-size: .78rem;
    color: var(--alert-success-color);
}
.t-result-meta span { display: flex; align-items: center; gap: .35rem; }
.t-result-meta i { color: var(--alert-success-color); }

/* ── Botones acción resultado ─────────────────────────────────────────────── */
.t-result-actions {
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}
.t-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .5rem .9rem;
    border-radius: 8px;
    font-size: .8rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: opacity .2s, transform .15s;
}
.t-btn:hover { opacity: .85; transform: translateY(-1px); }
.t-btn-copy     { background: var(--badge-light-bg); color: var(--accent); }
.t-btn-download { background: var(--alert-success-bg); color: var(--alert-success-color); }
.t-btn-clear    { background: var(--alert-danger-bg); color: var(--alert-danger-color); }

/* ── Toast ────────────────────────────────────────────────────────────────── */
.t-toast-container {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: .5rem;
}
.t-toast {
    display: flex;
    align-items: center;
    gap: .6rem;
    background: var(--card-bg);
    padding: .75rem 1rem;
    border-radius: 10px;
    box-shadow: var(--card-shadow);
    font-size: .82rem;
    font-weight: 500;
    color: var(--body-color);
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
<div class="ocr-wrap">

    {{-- ── Panel izquierdo: carga del PDF ──────────────────────────────────── --}}
    <div class="t-card">
        <div class="t-card-header">
            <div class="t-card-icon"><i class="fas fa-file-pdf"></i></div>
            <div>
                <h5>Extractor de Texto OCR</h5>
                <p>Cargá un PDF para extraer todo el texto con Tesseract</p>
            </div>
        </div>

        <form id="ocr-form" novalidate>
            @csrf

            {{-- Drop zone --}}
            <div class="drop-zone" id="drop-zone">
                <input type="file"
                       id="pdf-input"
                       name="pdf"
                       accept=".pdf,application/pdf">
                <i class="fas fa-file-arrow-up dz-icon"></i>
                <div class="dz-title">Arrastrá tu PDF aquí</div>
                <div class="dz-sub">o hacé clic para explorar</div>
                <div class="format-badges">
                    <span class="format-badge">PDF</span>
                </div>
            </div>

            {{-- Preview --}}
            <div class="file-preview" id="file-preview">
                <i class="fp-icon fas fa-file-pdf" style="color:#dc2626;"></i>
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
                    <span id="progress-label-text">Procesando con Tesseract OCR…</span>
                    <span></span>
                </div>
                <div class="t-progress-bar">
                    <div class="t-progress-bar-fill" id="progress-fill"></div>
                </div>
            </div>

            {{-- Botón --}}
            <button type="submit" class="btn-extraer" id="btn-extraer" disabled>
                <i class="fas fa-magnifying-glass-document"></i>
                Extraer Texto
            </button>
        </form>

        <div class="mt-3 pt-3" style="border-top:1px solid var(--card-border);">
            <p class="mb-0" style="font-size:.75rem;color:var(--muted-color);">
                <i class="fas fa-info-circle me-1" style="color:var(--accent);"></i>
                Tesseract procesa cada página del PDF como imagen. El tiempo
                depende de la cantidad de páginas y la calidad del escaneo.
                Tamaño máximo: <strong>50 MB</strong>.
            </p>
        </div>
    </div>

    {{-- ── Panel derecho: resultado ─────────────────────────────────────────── --}}
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
        <div class="t-result-meta" id="result-meta" style="display:none;"></div>

        {{-- Estado vacío --}}
        <div class="t-empty-state" id="empty-state">
            <i class="fas fa-file-dashed-line"></i>
            <p>El texto extraído aparecerá aquí<br>una vez procesado el PDF.</p>
        </div>

        {{-- Textarea editable --}}
        <textarea
            class="t-textarea"
            id="result-textarea"
            placeholder="El texto OCR aparecerá aquí…"
            style="display:none;"
        ></textarea>

        {{-- Acciones --}}
        <div class="t-result-actions" id="result-actions" style="display:none;">
            <button type="button" class="t-btn t-btn-copy" id="btn-copy">
                <i class="fas fa-copy"></i> Copiar Texto
            </button>
            <button type="button" class="t-btn t-btn-download" id="btn-download">
                <i class="fas fa-file-arrow-down"></i> Descargar como txt
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
    const form          = document.getElementById('ocr-form');
    const input         = document.getElementById('pdf-input');
    const dropZone      = document.getElementById('drop-zone');
    const filePreview   = document.getElementById('file-preview');
    const fileName      = document.getElementById('file-name');
    const fileSize      = document.getElementById('file-size');
    const btnRemove     = document.getElementById('btn-remove-file');
    const btnSubmit     = document.getElementById('btn-extraer');
    const progressWrap  = document.getElementById('progress-wrap');
    const progressFill  = document.getElementById('progress-fill');
    const emptyState    = document.getElementById('empty-state');
    const textarea      = document.getElementById('result-textarea');
    const resultMeta    = document.getElementById('result-meta');
    const resultActions = document.getElementById('result-actions');
    const btnCopy       = document.getElementById('btn-copy');
    const btnDownload   = document.getElementById('btn-download');
    const btnClear      = document.getElementById('btn-clear-result');
    const toastCont     = document.getElementById('toast-container');

    let currentFileName = 'texto-extraido.txt';

    // ── Helpers ────────────────────────────────────────────────────────────
    function formatBytes(bytes) {
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function toast(msg, type = 'info') {
        const icons = {
            success: 'fa-circle-check',
            error:   'fa-circle-exclamation',
            info:    'fa-circle-info'
        };
        const el = document.createElement('div');
        el.className = `t-toast toast-${type}`;
        el.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i><span>${msg}</span>`;
        toastCont.appendChild(el);
        setTimeout(() => el.remove(), 5000);
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    }

    // ── Archivo seleccionado ───────────────────────────────────────────────
    function setFilePreview(file) {
        currentFileName = file.name.replace(/\.pdf$/i, '') + '.txt';
        fileName.textContent = file.name;
        fileSize.textContent = formatBytes(file.size);
        filePreview.classList.add('show');
        btnSubmit.disabled = false;
    }

    function clearFile() {
        input.value = '';
        filePreview.classList.remove('show');
        btnSubmit.disabled = true;
        currentFileName = 'texto-extraido.txt';
    }

    input.addEventListener('change', () => {
        if (input.files[0]) setFilePreview(input.files[0]);
    });

    btnRemove.addEventListener('click', clearFile);

    // ── Drag & Drop ────────────────────────────────────────────────────────
    dropZone.addEventListener('dragover', e => {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });

    ['dragleave', 'dragend'].forEach(evt =>
        dropZone.addEventListener(evt, () => dropZone.classList.remove('drag-over'))
    );

    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const file = e.dataTransfer?.files[0];
        if (file) {
            if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
                toast('Solo se aceptan archivos PDF.', 'error');
                return;
            }
            // Transferir al input nativo
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            setFilePreview(file);
        }
    });

    // ── Progreso animado ───────────────────────────────────────────────────
    let progressInterval = null;

    function startProgress() {
        progressWrap.classList.add('show');
        progressFill.style.width = '0%';
        let pct = 0;
        progressInterval = setInterval(() => {
            // Simula progreso hasta 90 %; el 100 % llega al finalizar
            if (pct < 90) {
                pct += Math.random() * 3;
                progressFill.style.width = Math.min(pct, 90) + '%';
            }
        }, 400);
    }

    function finishProgress() {
        clearInterval(progressInterval);
        progressFill.style.width = '100%';
        setTimeout(() => {
            progressWrap.classList.remove('show');
            progressFill.style.width = '0%';
        }, 600);
    }

    // ── Mostrar resultado ──────────────────────────────────────────────────
    function showResult(text, chars, pages) {
        emptyState.style.display = 'none';
        textarea.style.display   = 'block';
        textarea.value           = text;

        resultMeta.style.display = 'flex';
        resultMeta.innerHTML = `
            <span><i class="fas fa-file-lines"></i> ${pages} pág.</span>
            <span><i class="fas fa-font"></i> ${chars.toLocaleString()} caracteres</span>
            <span><i class="fas fa-align-left"></i> ${text.split('\n').length.toLocaleString()} líneas</span>
        `;

        resultActions.style.display = 'flex';
    }

    function clearResult() {
        emptyState.style.display    = '';
        textarea.style.display      = 'none';
        resultMeta.style.display    = 'none';
        resultActions.style.display = 'none';
        textarea.value = '';
    }

    btnClear.addEventListener('click', clearResult);

    // ── Copiar ─────────────────────────────────────────────────────────────
    btnCopy.addEventListener('click', async () => {
        const text = textarea.value;
        if (!text) return;
        try {
            await navigator.clipboard.writeText(text);
            toast('Texto copiado al portapapeles.', 'success');
        } catch {
            // Fallback para contextos sin clipboard API
            textarea.select();
            document.execCommand('copy');
            toast('Texto copiado al portapapeles.', 'success');
        }
    });

    // ── Descargar ──────────────────────────────────────────────────────────
    btnDownload.addEventListener('click', () => {
        const text = textarea.value;
        if (!text) return;
        const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = currentFileName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        toast('Archivo descargado.', 'success');
    });

    // ── Envío del formulario ───────────────────────────────────────────────
    form.addEventListener('submit', async e => {
        e.preventDefault();

        if (!input.files[0]) {
            toast('Seleccioná un archivo PDF primero.', 'error');
            return;
        }

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando…';
        startProgress();

        const formData = new FormData();
        formData.append('pdf', input.files[0]);
        formData.append('_token', csrfToken());

        try {
            const res = await fetch('{{ route("pdf-extractor.extract") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                body: formData,
            });

            const data = await res.json();

            if (!res.ok) {
                throw new Error(data.error ?? 'Error desconocido al procesar el PDF.');
            }

            showResult(data.text, data.chars, data.pages);
            toast('Texto extraído correctamente.', 'success');

        } catch (err) {
            toast(err.message, 'error');
        } finally {
            finishProgress();
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-magnifying-glass-document"></i> Extraer Texto';
        }
    });

})();
</script>
@endpush
