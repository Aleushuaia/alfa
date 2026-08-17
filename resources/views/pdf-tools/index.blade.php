@extends('layouts.dashboard')

@section('title', 'Herramientas PDF — ' . config('app.name', 'Alfa'))
@section('page-title', 'Herramientas PDF')
@section('breadcrumb', 'Herramientas PDF')

@push('styles')
<style>
/* ── Layout principal ─────────────────────────────────────────────────────── */
.pt-wrap {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 1.25rem;
    align-items: start;
}
@media (max-width: 960px) {
    .pt-wrap { grid-template-columns: 1fr; }
}

/* ── Tarjeta base ─────────────────────────────────────────────────────────── */
.t-card {
    background: var(--card-bg);
    border-radius: var(--card-radius, 14px);
    box-shadow: var(--card-shadow, 0 2px 20px rgba(0,0,0,.07));
    border: 1px solid var(--card-border);
    color: var(--body-color);
    overflow: hidden;
}
.t-card-head {
    display: flex;
    align-items: center;
    gap: .65rem;
    padding: .85rem 1.1rem;
    border-bottom: 1px solid var(--card-border);
}
.t-card-icon {
    width: 36px; height: 36px;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0; color: #fff;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    box-shadow: 0 3px 10px var(--accent-glow, rgba(59,130,246,.3));
}
.t-card-icon.green  { background: linear-gradient(135deg,#059669,#10b981); box-shadow: 0 3px 10px rgba(16,185,129,.3); }
.t-card-icon.red    { background: linear-gradient(135deg,#dc2626,#ef4444); box-shadow: 0 3px 10px rgba(220,38,38,.3); }
.t-card-icon.amber  { background: linear-gradient(135deg,#d97706,#f59e0b); box-shadow: 0 3px 10px rgba(217,119,6,.3); }
.t-card-head h5 {
    margin: 0; font-size: .92rem; font-weight: 700;
    color: var(--heading-color); line-height: 1.25;
}
.t-card-head p {
    margin: 0; font-size: .73rem; color: var(--muted-color);
}
.t-card-body { padding: 1.1rem; }

/* ── Drop zone ────────────────────────────────────────────────────────────── */
.drop-zone {
    border: 2px dashed var(--input-border);
    border-radius: 12px;
    padding: 2rem 1.25rem;
    text-align: center;
    cursor: pointer;
    transition: border-color .25s, background .25s;
    background: var(--input-bg);
    position: relative;
}
.drop-zone:hover, .drop-zone.drag-over {
    border-color: var(--accent);
    background: color-mix(in srgb, var(--accent) 6%, var(--input-bg));
}
.drop-zone input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer;
    width: 100%; height: 100%;
}
.dz-icon  { font-size: 2.2rem; color: var(--accent); margin-bottom: .6rem; display: block; }
.dz-title { font-size: .9rem; font-weight: 600; color: var(--heading-color); margin-bottom: .2rem; }
.dz-sub   { font-size: .78rem; color: var(--muted-color); }
.fmt-badges { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .8rem; justify-content: center; }
.fmt-badge  {
    background: var(--badge-light-bg); color: var(--badge-light-color);
    border: 1px solid var(--badge-light-border); border-radius: 5px;
    font-size: .65rem; font-weight: 700; padding: .1rem .38rem; letter-spacing: .04em;
}

/* ── File preview ─────────────────────────────────────────────────────────── */
.file-preview {
    display: none; align-items: center; gap: .65rem;
    background: var(--badge-light-bg); border: 1px solid var(--badge-light-border);
    border-radius: 9px; padding: .6rem .8rem; margin-top: .7rem;
}
.file-preview.show { display: flex; }
.fp-icon  { font-size: 1.35rem; color: #dc2626; flex-shrink: 0; }
.fp-info  { flex: 1; min-width: 0; }
.fp-name  { font-size: .8rem; font-weight: 600; color: var(--heading-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fp-size  { font-size: .71rem; color: var(--muted-color); }
.fp-remove { background: none; border: none; color: #94a3b8; font-size: 1rem; cursor: pointer; padding: 0 .2rem; flex-shrink: 0; transition: color .2s; }
.fp-remove:hover { color: #ef4444; }

/* ── Herramientas ─────────────────────────────────────────────────────────── */
.tools-section {
    margin-top: 1.1rem;
    padding-top: 1.1rem;
    border-top: 1px solid var(--card-border);
    display: none;
}
.tools-section.visible { display: block; }
.tools-label {
    font-size: .72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: var(--muted-color); margin-bottom: .65rem;
}
.tool-buttons { display: flex; flex-direction: column; gap: .55rem; }

.btn-tool {
    width: 100%;
    display: flex; align-items: center; gap: .6rem;
    padding: .62rem .9rem;
    border-radius: 9px; border: none; cursor: pointer;
    font-size: .85rem; font-weight: 600;
    transition: opacity .18s, transform .12s, box-shadow .18s;
    text-align: left;
}
.btn-tool:hover:not(:disabled) { opacity: .88; transform: translateY(-1px); }
.btn-tool:disabled { opacity: .45; cursor: not-allowed; }
.btn-tool .tool-icon {
    width: 30px; height: 30px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; flex-shrink: 0; color: #fff;
}
.btn-tool .tool-text { flex: 1; }
.btn-tool .tool-text small { display: block; font-size: .68rem; font-weight: 400; opacity: .8; }

.btn-ocr  {
    background: color-mix(in srgb,#2563eb 10%,var(--card-bg));
    color: var(--heading-color);
    border: 1px solid color-mix(in srgb,#2563eb 25%,transparent);
}
.btn-ocr .tool-icon  { background: linear-gradient(135deg,#2563eb,#1d4ed8); box-shadow: 0 2px 8px rgba(37,99,235,.28); }

.btn-compress {
    background: color-mix(in srgb,#d97706 10%,var(--card-bg));
    color: var(--heading-color);
    border: 1px solid color-mix(in srgb,#d97706 25%,transparent);
}
.btn-compress .tool-icon { background: linear-gradient(135deg,#d97706,#b45309); box-shadow: 0 2px 8px rgba(217,119,6,.28); }

/* ── Select nivel compresión ──────────────────────────────────────────────── */
.compress-opts {
    display: none;
    margin-top: .5rem;
    background: var(--input-bg);
    border: 1px solid var(--input-border);
    border-radius: 8px;
    padding: .6rem .75rem;
}
.compress-opts.visible { display: block; }
.compress-opts label {
    display: block; font-size: .71rem; font-weight: 600; color: var(--muted-color);
    margin-bottom: .3rem; text-transform: uppercase; letter-spacing: .04em;
}
.compress-opts select {
    width: 100%;
    padding: .38rem .6rem;
    font-size: .8rem;
    color: var(--input-color);
    background: var(--card-bg);
    border: 1px solid var(--input-border);
    border-radius: 6px;
    outline: none;
    cursor: pointer;
}

/* ── Barra de progreso ────────────────────────────────────────────────────── */
.t-progress-wrap { display: none; margin-top: .85rem; }
.t-progress-wrap.show { display: block; }
.t-progress-label { font-size: .74rem; color: var(--muted-color); margin-bottom: .28rem; display: flex; justify-content: space-between; }
.t-progress-bar { height: 5px; border-radius: 99px; background: var(--badge-light-bg); overflow: hidden; }
.t-progress-fill {
    height: 100%; width: 0%; border-radius: 99px;
    background: linear-gradient(90deg, var(--accent), var(--accent2));
    transition: width .4s;
    animation: pgpulse 1.4s ease-in-out infinite;
}
.t-progress-fill.amber { background: linear-gradient(90deg,#d97706,#f59e0b); }
@keyframes pgpulse { 0%,100%{opacity:1} 50%{opacity:.55} }

/* ── Panel resultado OCR ──────────────────────────────────────────────────── */
.result-panel { display: none; }
.result-panel.show { display: flex; flex-direction: column; }

.t-result-meta {
    display: flex; gap: .8rem; flex-wrap: wrap;
    padding: .5rem .85rem;
    background: var(--alert-success-bg);
    border: 1px solid var(--alert-success-color);
    border-radius: 8px; font-size: .76rem;
    color: var(--alert-success-color);
    margin-bottom: .75rem;
}
.t-result-meta span { display: flex; align-items: center; gap: .3rem; }

.t-textarea {
    width: 100%; min-height: 320px;
    border: 1.5px solid var(--input-border); border-radius: 10px;
    padding: .9rem 1rem; font-size: .84rem; font-family: 'Inter', sans-serif;
    color: var(--input-color); line-height: 1.7; resize: vertical;
    background: var(--input-bg); transition: border-color .2s; outline: none;
}
.t-textarea:focus { border-color: var(--accent); }

.t-result-actions { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .75rem; }
.t-btn {
    display: inline-flex; align-items: center; gap: .38rem;
    padding: .45rem .85rem; border-radius: 8px;
    font-size: .78rem; font-weight: 600;
    border: none; cursor: pointer;
    transition: opacity .18s, transform .13s;
}
.t-btn:hover:not(:disabled) { opacity: .82; transform: translateY(-1px); }
.t-btn-copy     { background: var(--badge-light-bg); color: var(--accent); border: 1px solid var(--badge-light-border); }
.t-btn-download { background: var(--alert-success-bg); color: var(--alert-success-color); }
.t-btn-clear    { background: var(--alert-danger-bg,#fee2e2); color: var(--alert-danger-color,#dc2626); }

/* ── Panel resultado compresión ───────────────────────────────────────────── */
.compress-result {
    display: none; flex-direction: column;
    align-items: center; padding: 1.75rem 1rem; text-align: center;
}
.compress-result.show { display: flex; }
.cr-icon { font-size: 2.5rem; color: #059669; margin-bottom: .65rem; }
.cr-title { font-size: 1rem; font-weight: 700; color: var(--heading-color); margin-bottom: .3rem; }
.cr-stats {
    display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;
    margin: .75rem 0 1.1rem;
}
.cr-stat {
    background: var(--badge-light-bg); border: 1px solid var(--badge-light-border);
    border-radius: 9px; padding: .55rem .9rem; font-size: .8rem;
    color: var(--body-color);
}
.cr-stat strong { display: block; font-size: .95rem; color: var(--heading-color); }
.cr-reduction {
    background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3);
    color: #059669;
}
.btn-download-pdf {
    display: inline-flex; align-items: center; gap: .45rem;
    padding: .62rem 1.4rem; border-radius: 9px; border: none;
    background: linear-gradient(135deg,#059669,#10b981);
    color: #fff; font-size: .9rem; font-weight: 600; cursor: pointer;
    text-decoration: none;
    box-shadow: 0 3px 12px rgba(16,185,129,.3);
    transition: opacity .18s, transform .13s;
}
.btn-download-pdf:hover { opacity: .88; transform: translateY(-1px); color: #fff; }

/* ── Estado vacío ─────────────────────────────────────────────────────────── */
.t-empty {
    text-align: center; padding: 3.5rem 1rem;
    color: var(--muted-color);
}
.t-empty i  { font-size: 2.8rem; display: block; margin-bottom: .9rem; }
.t-empty p  { font-size: .84rem; line-height: 1.65; margin: 0; }

/* ── Toast ────────────────────────────────────────────────────────────────── */
.t-toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; display: flex; flex-direction: column; gap: .45rem; }
.t-toast {
    display: flex; align-items: center; gap: .55rem;
    background: var(--card-bg); padding: .7rem 1rem; border-radius: 10px;
    box-shadow: var(--card-shadow); font-size: .81rem; font-weight: 500;
    color: var(--body-color); border-left: 4px solid var(--accent);
    animation: slideInRight .28s ease; min-width: 240px; max-width: 340px;
}
.t-toast.toast-error   { border-left-color: #ef4444; }
.t-toast.toast-success { border-left-color: #22c55e; }
.t-toast i { font-size: 1rem; }
.t-toast.toast-error   i { color: #ef4444; }
.t-toast.toast-success i { color: #22c55e; }
@keyframes slideInRight { from{opacity:0;transform:translateX(28px)} to{opacity:1;transform:translateX(0)} }
</style>
@endpush

@section('content')
<div class="pt-wrap">

    {{-- ── Panel izquierdo: carga y herramientas ───────────────────────────── --}}
    <div class="t-card">
        <div class="t-card-head">
            <div class="t-card-icon red"><i class="fas fa-file-pdf"></i></div>
            <div>
                <h5>Subir documento PDF</h5>
                <p>Arrastrá o hacé clic para seleccionar PDF</p>
            </div>
        </div>
        <div class="t-card-body">

            {{-- Drop zone --}}
            <div class="drop-zone" id="drop-zone">
                <input type="file" id="pdf-input" accept=".pdf,application/pdf">
                <i class="fas fa-file-pdf dz-icon" style="color:#dc2626;"></i>
                <div class="dz-title">Arrastrá o hacé clic</div>
                <div class="dz-sub">para seleccionar PDF</div>
                <div class="fmt-badges"><span class="fmt-badge" style="background:color-mix(in srgb,#dc2626 12%,var(--badge-light-bg));border-color:color-mix(in srgb,#dc2626 25%,transparent);color:#dc2626;">PDF · máx. 100 MB</span></div>
            </div>

            {{-- Preview --}}
            <div class="file-preview" id="file-preview">
                <i class="fp-icon fas fa-file-pdf"></i>
                <div class="fp-info">
                    <div class="fp-name" id="fp-name">—</div>
                    <div class="fp-size" id="fp-size">—</div>
                </div>
                <button type="button" class="fp-remove" id="btn-remove"><i class="fas fa-times-circle"></i></button>
            </div>

            {{-- Herramientas --}}
            <div class="tools-section" id="tools-section">
                <div class="tools-label">Seleccioná una herramienta</div>

                <div class="tool-buttons">

                    {{-- OCR --}}
                    <button type="button" class="btn-tool btn-ocr" id="btn-ocr" disabled>
                        <span class="tool-icon"><i class="fas fa-file-image"></i></span>
                        <span class="tool-text">
                            Extracción OCR
                        </span>
                        <i class="fas fa-chevron-right" style="opacity:.4;font-size:.75rem;"></i>
                    </button>

                    {{-- Comprimir --}}
                    <button type="button" class="btn-tool btn-compress" id="btn-compress-toggle" disabled>
                        <span class="tool-icon"><i class="fas fa-file-zipper"></i></span>
                        <span class="tool-text">
                            Comprimir PDF
                        </span>
                        <i class="fas fa-chevron-down" id="compress-chevron" style="opacity:.4;font-size:.75rem;"></i>
                    </button>

                    {{-- Opciones de compresión (ocultas hasta hacer clic en el botón) --}}
                    <div class="compress-opts" id="compress-opts">
                        <label>Nivel de compresión</label>
                        <select id="compress-level">
                            @foreach($levels as $key => $info)
                            <option value="{{ $key }}" {{ $key === 'ebook' ? 'selected' : '' }}>
                                {{ $info['label'] }} — {{ $info['desc'] }}
                            </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn-tool btn-compress mt-2" id="btn-compress-run" style="margin-top:.55rem;">
                            <span class="tool-icon"><i class="fas fa-rocket"></i></span>
                            <span class="tool-text">Comprimir ahora</span>
                        </button>
                    </div>

                </div>

                {{-- Barra de progreso --}}
                <div class="t-progress-wrap" id="progress-wrap">
                    <div class="t-progress-label">
                        <span id="progress-text">Procesando…</span>
                    </div>
                    <div class="t-progress-bar">
                        <div class="t-progress-fill" id="progress-fill"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Panel derecho: resultado ─────────────────────────────────────────── --}}
    <div class="t-card" id="result-card" style="min-height:60vh;display:flex;flex-direction:column;">
        <div class="t-card-head">
            <div class="t-card-icon green"><i class="fas fa-file-lines"></i></div>
            <div>
                <h5 id="result-card-title">Resultado</h5>
                <p id="result-card-sub">El resultado del proceso aparecerá aquí</p>
            </div>
        </div>
        <div class="t-card-body" style="flex:1;display:flex;flex-direction:column;">

            {{-- Estado vacío --}}
            <div class="t-empty" id="empty-state">
                <i class="fas fa-file-dashed-line"></i>
                <p>Cargá un PDF y elegí una herramienta<br>para ver el resultado aquí.</p>
            </div>

            {{-- Panel OCR --}}
            <div class="result-panel" id="panel-ocr">
                <div class="t-result-meta" id="ocr-meta"></div>
                <textarea class="t-textarea" id="ocr-textarea" placeholder="El texto OCR aparecerá aquí…"></textarea>
                <div class="t-result-actions">
                    <button type="button" class="t-btn t-btn-copy" id="btn-copy">
                        <i class="fas fa-copy"></i> Copiar texto
                    </button>
                    <button type="button" class="t-btn t-btn-download" id="btn-download-txt">
                        <i class="fas fa-file-arrow-down"></i> Descargar .txt
                    </button>
                    <button type="button" class="t-btn t-btn-clear" id="btn-clear-ocr">
                        <i class="fas fa-trash-alt"></i> Limpiar
                    </button>
                </div>
            </div>

            {{-- Panel Compresión --}}
            <div class="compress-result" id="panel-compress">
                <i class="fas fa-file-zipper cr-icon" style="color:#d97706;"></i>
                <div class="cr-title">¡Compresión completada!</div>
                <p style="font-size:.81rem;color:var(--muted-color);margin:.25rem 0 0;">
                    El archivo fue comprimido exitosamente!
                </p>
                <div class="cr-stats" id="compress-stats"></div>
                <a href="#" class="btn-download-pdf" id="btn-download-pdf">
                    <i class="fas fa-file-arrow-down"></i> Descargar PDF comprimido
                </a>
                <button type="button" class="t-btn t-btn-clear mt-3" id="btn-clear-compress" style="margin-top:.85rem;">
                    <i class="fas fa-arrow-rotate-left"></i> Nueva compresión
                </button>
            </div>

        </div>
    </div>

</div>

<div class="t-toast-container" id="toast-container"></div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ── DOM ────────────────────────────────────────────────────────────────
    const dropZone        = document.getElementById('drop-zone');
    const pdfInput        = document.getElementById('pdf-input');
    const filePreview     = document.getElementById('file-preview');
    const fpName          = document.getElementById('fp-name');
    const fpSize          = document.getElementById('fp-size');
    const btnRemove       = document.getElementById('btn-remove');
    const toolsSection    = document.getElementById('tools-section');
    const btnOcr          = document.getElementById('btn-ocr');
    const btnCompressToggle = document.getElementById('btn-compress-toggle');
    const compressOpts    = document.getElementById('compress-opts');
    const compressChevron = document.getElementById('compress-chevron');
    const compressLevel   = document.getElementById('compress-level');
    const btnCompressRun  = document.getElementById('btn-compress-run');
    const progressWrap    = document.getElementById('progress-wrap');
    const progressFill    = document.getElementById('progress-fill');
    const progressText    = document.getElementById('progress-text');

    // OCR result
    const emptyState      = document.getElementById('empty-state');
    const panelOcr        = document.getElementById('panel-ocr');
    const ocrMeta         = document.getElementById('ocr-meta');
    const ocrTextarea     = document.getElementById('ocr-textarea');
    const btnCopy         = document.getElementById('btn-copy');
    const btnDownloadTxt  = document.getElementById('btn-download-txt');
    const btnClearOcr     = document.getElementById('btn-clear-ocr');

    // Compress result
    const panelCompress   = document.getElementById('panel-compress');
    const compressStats   = document.getElementById('compress-stats');
    const btnDownloadPdf  = document.getElementById('btn-download-pdf');
    const btnClearCompress = document.getElementById('btn-clear-compress');

    const toastCont       = document.getElementById('toast-container');

    const resultCardTitle = document.getElementById('result-card-title');
    const resultCardSub   = document.getElementById('result-card-sub');

    let currentFile     = null;
    let ocrFileName     = 'texto-extraido.txt';
    let progressTimer   = null;

    // ── Helpers ────────────────────────────────────────────────────────────
    function fmtBytes(b) {
        return b < 1048576 ? (b / 1024).toFixed(1) + ' KB' : (b / 1048576).toFixed(2) + ' MB';
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    }

    function toast(msg, type = 'info') {
        const icons = { success: 'fa-circle-check', error: 'fa-circle-exclamation', info: 'fa-circle-info' };
        const el = document.createElement('div');
        el.className = `t-toast toast-${type}`;
        el.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i><span>${msg}</span>`;
        toastCont.appendChild(el);
        setTimeout(() => el.remove(), 5000);
    }

    // ── Progreso ───────────────────────────────────────────────────────────
    function startProgress(label, colorClass = '') {
        progressWrap.classList.add('show');
        progressFill.className = 't-progress-fill' + (colorClass ? ' ' + colorClass : '');
        progressText.textContent = label;
        let pct = 0;
        progressTimer = setInterval(() => {
            if (pct < 88) { pct += Math.random() * 3; progressFill.style.width = Math.min(pct, 88) + '%'; }
        }, 400);
    }

    function stopProgress() {
        clearInterval(progressTimer);
        progressFill.style.width = '100%';
        setTimeout(() => {
            progressWrap.classList.remove('show');
            progressFill.style.width = '0%';
        }, 600);
    }

    // ── Archivo ────────────────────────────────────────────────────────────
    function setFile(file) {
        currentFile = file;
        ocrFileName = file.name.replace(/\.pdf$/i, '') + '.txt';
        fpName.textContent = file.name;
        fpSize.textContent = fmtBytes(file.size);
        filePreview.classList.add('show');
        toolsSection.classList.add('visible');
        btnOcr.disabled = false;
        btnCompressToggle.disabled = false;
        btnCompressRun.disabled = false;
    }

    function clearFile() {
        currentFile = null;
        pdfInput.value = '';
        filePreview.classList.remove('show');
        toolsSection.classList.remove('visible');
        compressOpts.classList.remove('visible');
        compressChevron.className = 'fas fa-chevron-down';
        compressChevron.style.opacity = '.4';
        btnOcr.disabled = true;
        btnCompressToggle.disabled = true;
        btnCompressRun.disabled = true;
        hideResults();
    }

    function hideResults() {
        emptyState.style.display = '';
        panelOcr.classList.remove('show');
        panelCompress.classList.remove('show');
        resultCardTitle.textContent = 'Resultado';
        resultCardSub.textContent   = 'El resultado del proceso aparecerá aquí';
    }

    pdfInput.addEventListener('change', () => { if (pdfInput.files[0]) setFile(pdfInput.files[0]); });
    btnRemove.addEventListener('click', clearFile);

    // ── Drag & Drop ────────────────────────────────────────────────────────
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    ['dragleave', 'dragend'].forEach(evt => dropZone.addEventListener(evt, () => dropZone.classList.remove('drag-over')));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const f = e.dataTransfer?.files[0];
        if (!f) return;
        if (!f.name.toLowerCase().endsWith('.pdf') && f.type !== 'application/pdf') {
            toast('Solo se aceptan archivos PDF.', 'error'); return;
        }
        const dt = new DataTransfer(); dt.items.add(f); pdfInput.files = dt.files;
        setFile(f);
    });

    // ── Toggle opciones compresión ─────────────────────────────────────────
    btnCompressToggle.addEventListener('click', () => {
        const visible = compressOpts.classList.toggle('visible');
        compressChevron.className = visible ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
        compressChevron.style.opacity = visible ? '.8' : '.4';
    });

    // ── OCR ───────────────────────────────────────────────────────────────
    btnOcr.addEventListener('click', async () => {
        if (!currentFile) return;
        btnOcr.disabled = true;
        btnCompressToggle.disabled = true;
        startProgress('Procesando OCR…');
        hideResults();
        emptyState.style.display = 'none';

        const fd = new FormData();
        fd.append('_token', csrfToken());
        fd.append('pdf', currentFile);

        try {
            const res  = await fetch('{{ route("pdf-tools.ocr") }}', { method: 'POST', body: fd });
            const data = await res.json();
            if (!res.ok || data.error) { toast(data.error || 'Error al procesar OCR.', 'error'); return; }

            stopProgress();
            ocrTextarea.value = data.text;
            ocrMeta.innerHTML = `
                <span><i class="fas fa-file-lines"></i> ${data.pages} pág.</span>
                <span><i class="fas fa-font"></i> ${data.chars.toLocaleString()} caracteres</span>
                <span><i class="fas fa-align-left"></i> ${data.text.split('\n').length.toLocaleString()} líneas</span>
            `;
            panelOcr.classList.add('show');
            resultCardTitle.textContent = 'Texto extraído (OCR)';
            resultCardSub.textContent   = 'Podés editar el resultado antes de copiarlo o descargarlo';
            toast('Texto extraído correctamente.', 'success');
        } catch (err) {
            toast('Error de red: ' + err.message, 'error');
        } finally {
            stopProgress();
            btnOcr.disabled = false;
            btnCompressToggle.disabled = false;
        }
    });

    // ── Copiar / descargar TXT ─────────────────────────────────────────────
    btnCopy.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(ocrTextarea.value);
            toast('Texto copiado al portapapeles.', 'success');
        } catch {
            ocrTextarea.select(); document.execCommand('copy');
            toast('Texto copiado.', 'success');
        }
    });

    btnDownloadTxt.addEventListener('click', () => {
        const blob = new Blob([ocrTextarea.value], { type: 'text/plain;charset=utf-8' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href = url; a.download = ocrFileName; a.click();
        URL.revokeObjectURL(url);
    });

    btnClearOcr.addEventListener('click', () => { panelOcr.classList.remove('show'); emptyState.style.display = ''; });

    // ── Compresión ────────────────────────────────────────────────────────
    btnCompressRun.addEventListener('click', async () => {
        if (!currentFile) return;
        btnOcr.disabled = true;
        btnCompressToggle.disabled = true;
        btnCompressRun.disabled = true;
        const level = compressLevel.value;
        startProgress('Comprimiendo PDF…', 'amber');
        hideResults();
        emptyState.style.display = 'none';

        const fd = new FormData();
        fd.append('_token', csrfToken());
        fd.append('pdf', currentFile);
        fd.append('level', level);

        try {
            const res = await fetch('{{ route("pdf-tools.compress") }}', { method: 'POST', body: fd });

            if (!res.ok) {
                const err = await res.json().catch(() => ({ error: 'Error desconocido.' }));
                toast(err.error || 'Error al comprimir.', 'error');
                return;
            }

            // Leer headers antes de consumir el body como blob
            const origSize  = parseInt(res.headers.get('X-Original-Size')   || '0');
            const compSize  = parseInt(res.headers.get('X-Compressed-Size') || '0');
            const reduction = parseFloat(res.headers.get('X-Reduction-Percent') || '0');

            const blob = await res.blob();
            stopProgress();

            // Mostrar panel de resultados
            compressStats.innerHTML = `
                <div class="cr-stat"><strong>${origSize > 0 ? (origSize/1024).toFixed(0) + ' KB' : '—'}</strong>Tamaño original</div>
                <div class="cr-stat"><strong>${compSize > 0 ? (compSize/1024).toFixed(0) + ' KB' : '—'}</strong>Tamaño final</div>
                <div class="cr-stat cr-reduction"><strong>${reduction > 0 ? reduction + '%' : '—'}</strong>Reducción</div>
            `;

            // Crear URL de descarga en memoria
            const url  = URL.createObjectURL(blob);
            btnDownloadPdf.href = url;
            const baseName = currentFile.name.replace(/\.pdf$/i, '');
            btnDownloadPdf.download = `${baseName}_${level}_comprimido.pdf`;

            panelCompress.classList.add('show');
            resultCardTitle.textContent = 'PDF comprimido';
            resultCardSub.textContent   = 'Proceso completado — descargá el resultado';
            toast('¡PDF comprimido correctamente!', 'success');

        } catch (err) {
            toast('Error de red: ' + err.message, 'error');
        } finally {
            stopProgress();
            btnOcr.disabled = false;
            btnCompressToggle.disabled = false;
            btnCompressRun.disabled = false;
        }
    });

    btnClearCompress.addEventListener('click', () => {
        panelCompress.classList.remove('show');
        emptyState.style.display = '';
        resultCardTitle.textContent = 'Resultado';
        resultCardSub.textContent   = 'El resultado del proceso aparecerá aquí';
    });

})();
</script>
@endpush
