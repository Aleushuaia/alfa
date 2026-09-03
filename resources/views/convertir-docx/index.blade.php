@extends('layouts.dashboard')

@section('title', 'Convertir a DocX — ' . config('app.name', 'Alfa'))
@section('page-title', 'Convertir a DocX')
@section('breadcrumb', 'Convertir a DocX')

@push('styles')
<style>
/* ── Layout principal ─────────────────────────────────────────────────────── */
.cdx-wrap {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 1.25rem;
    align-items: stretch;
}
@media (min-width: 961px) {
    .cdx-wrap { min-height: calc(100vh - var(--topbar-h) - 7rem); }
    .cdx-wrap > .t-card { display: flex; flex-direction: column; }
    .cdx-wrap > .t-card > .t-card-body { flex: 1; }
}
@media (max-width: 960px) {
    .cdx-wrap { grid-template-columns: 1fr; align-items: start; }
}

.t-card {
    background: var(--card-bg);
    border-radius: var(--card-radius, 14px);
    box-shadow: var(--card-shadow, 0 2px 20px rgba(0,0,0,.07));
    border: 1px solid var(--card-border);
    color: var(--body-color);
    overflow: hidden;
}
.t-card-head {
    display: flex; align-items: center; gap: .65rem;
    padding: .85rem 1.1rem; border-bottom: 1px solid var(--card-border);
}
.t-card-icon {
    width: 36px; height: 36px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0; color: #fff;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    box-shadow: 0 3px 10px var(--accent-glow, rgba(59,130,246,.3));
}
.t-card-icon.green  { background: linear-gradient(135deg,#059669,#10b981); box-shadow: 0 3px 10px rgba(16,185,129,.3); }
.t-card-icon.blue   { background: linear-gradient(135deg,#2563eb,#1d4ed8); box-shadow: 0 3px 10px rgba(37,99,235,.3); }
.t-card-head h5 { margin: 0; font-size: .92rem; font-weight: 700; color: var(--heading-color); line-height: 1.25; }
.t-card-head p  { margin: 0; font-size: .73rem; color: var(--muted-color); }
.t-card-body { padding: 1.1rem; }

.fn-label {
    font-size: .72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: var(--muted-color); margin-bottom: .65rem;
}

/* ── Dropzone ─────────────────────────────────────────────────────────────── */
.drop-zone {
    border: 2px dashed var(--input-border);
    border-radius: 12px; padding: 1.8rem 1.1rem; text-align: center;
    cursor: pointer; transition: border-color .25s, background .25s;
    background: var(--input-bg); position: relative;
}
.drop-zone:hover, .drop-zone.drag-over {
    border-color: var(--accent);
    background: color-mix(in srgb, var(--accent) 6%, var(--input-bg));
}
.drop-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.dz-icon  { font-size: 1.9rem; color: var(--accent); margin-bottom: .5rem; display: block; }
.dz-title { font-size: .87rem; font-weight: 600; color: var(--heading-color); margin-bottom: .2rem; }
.dz-sub   { font-size: .76rem; color: var(--muted-color); }
.fmt-badge {
    display: inline-block; margin-top: .6rem;
    background: var(--badge-light-bg); color: var(--badge-light-color);
    border: 1px solid var(--badge-light-border); border-radius: 5px;
    font-size: .65rem; font-weight: 700; padding: .1rem .38rem; letter-spacing: .04em;
}

.file-preview {
    display: none; align-items: center; gap: .65rem;
    background: var(--badge-light-bg); border: 1px solid var(--badge-light-border);
    border-radius: 9px; padding: .6rem .8rem; margin-top: .7rem;
}
.file-preview.show { display: flex; }
.fp-icon  { font-size: 1.3rem; color: #2563eb; flex-shrink: 0; }
.fp-info  { flex: 1; min-width: 0; }
.fp-name  { font-size: .8rem; font-weight: 600; color: var(--heading-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fp-size  { font-size: .71rem; color: var(--muted-color); }
.fp-remove { background: none; border: none; color: #94a3b8; font-size: 1rem; cursor: pointer; padding: 0 .2rem; flex-shrink: 0; }
.fp-remove:hover { color: #ef4444; }

.btn-run {
    width: 100%; margin-top: .8rem; display: flex; align-items: center; justify-content: center; gap: .5rem;
    padding: .62rem .9rem; border-radius: 9px; border: none; cursor: pointer;
    font-size: .85rem; font-weight: 700; color: #fff; transition: opacity .18s, transform .12s;
    background: linear-gradient(135deg,#2563eb,#1d4ed8);
}
.btn-run:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
.btn-run:disabled { opacity: .45; cursor: not-allowed; }

.hint-box {
    margin-top: 1rem; font-size: .74rem; line-height: 1.5; color: var(--muted-color);
    background: var(--input-bg); border: 1px solid var(--input-border);
    border-radius: 8px; padding: .65rem .75rem;
}

/* ── Barra de progreso ────────────────────────────────────────────────────── */
.t-progress-wrap { display: none; margin-top: .85rem; }
.t-progress-wrap.show { display: block; }
.t-progress-label { font-size: .74rem; color: var(--muted-color); margin-bottom: .28rem; display: flex; justify-content: space-between; }
.t-progress-bar { height: 5px; border-radius: 99px; background: var(--badge-light-bg); overflow: hidden; }
.t-progress-fill { height: 100%; width: 0%; border-radius: 99px; background: linear-gradient(90deg, var(--accent), var(--accent2)); transition: width .4s; animation: pgpulse 1.4s ease-in-out infinite; }
@keyframes pgpulse { 0%,100%{opacity:1} 50%{opacity:.55} }

/* ── Panel de resultados ──────────────────────────────────────────────────── */
.t-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; text-align: center; color: var(--muted-color); gap: .6rem; padding: 2rem 1rem; }
.t-empty i { font-size: 2.4rem; opacity: .35; }
.t-empty p { margin: 0; font-size: .85rem; }

.result-panel { display: none; flex: 1; flex-direction: column; gap: .9rem; }
.result-panel.show { display: flex; }

.doc-row {
    display: flex; align-items: center; gap: .8rem;
    background: var(--input-bg); border: 1px solid var(--input-border);
    border-radius: 10px; padding: .8rem .9rem;
}
.doc-row .dr-icon { font-size: 1.6rem; flex-shrink: 0; }
.doc-row.src .dr-icon { color: #64748b; }
.doc-row.dst .dr-icon { color: #2563eb; }
.dr-info { flex: 1; min-width: 0; }
.dr-label { font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted-color); }
.dr-name { font-size: .85rem; font-weight: 600; color: var(--heading-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dr-meta { font-size: .72rem; color: var(--muted-color); margin-top: .1rem; }
.dr-chip {
    display: inline-block; margin-top: .25rem; font-size: .66rem; font-weight: 700;
    padding: .12rem .45rem; border-radius: 5px;
    background: color-mix(in srgb,#2563eb 12%,var(--badge-light-bg));
    border: 1px solid color-mix(in srgb,#2563eb 25%,transparent); color: #2563eb;
}
.status-badge {
    flex-shrink: 0; font-size: .68rem; font-weight: 700; padding: .2rem .5rem; border-radius: 6px;
}
.status-badge.converted   { background: color-mix(in srgb,#10b981 15%,var(--card-bg)); color: #059669; border: 1px solid color-mix(in srgb,#10b981 35%,transparent); }
.status-badge.passthrough { background: color-mix(in srgb,#2563eb 12%,var(--card-bg)); color: #2563eb; border: 1px solid color-mix(in srgb,#2563eb 30%,transparent); }

.arrow-sep { text-align: center; color: var(--muted-color); font-size: .9rem; margin: -.35rem 0; }

.warn-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .4rem; }
.warn-list li {
    display: flex; gap: .5rem; align-items: flex-start;
    font-size: .76rem; line-height: 1.45; color: var(--heading-color);
    background: color-mix(in srgb,#f59e0b 10%,var(--card-bg));
    border: 1px solid color-mix(in srgb,#f59e0b 32%,transparent);
    border-radius: 8px; padding: .55rem .7rem;
}
.warn-list li i { color: #d97706; margin-top: .1rem; flex-shrink: 0; }

.result-note { font-size: .74rem; color: var(--muted-color); }

.btn-download-docx {
    display: inline-flex; align-items: center; gap: .5rem; align-self: flex-start;
    padding: .6rem 1.2rem; border-radius: 9px; text-decoration: none;
    background: linear-gradient(135deg,#2563eb,#1d4ed8); color: #fff;
    border: none; font-weight: 700; font-size: .85rem; cursor: pointer;
}
.btn-download-docx:hover { opacity: .9; color: #fff; }

/* ── Panel de anomalía ────────────────────────────────────────────────────── */
.anomaly-panel { display: none; flex: 1; flex-direction: column; gap: .9rem; }
.anomaly-panel.show { display: flex; }
.anomaly-box {
    display: flex; gap: .7rem; align-items: flex-start;
    background: color-mix(in srgb,#ef4444 9%,var(--card-bg));
    border: 1px solid color-mix(in srgb,#ef4444 32%,transparent);
    border-radius: 10px; padding: .85rem .95rem;
}
.anomaly-box i { color: #ef4444; font-size: 1.2rem; margin-top: .1rem; flex-shrink: 0; }
.anomaly-box .ab-title { font-size: .85rem; font-weight: 700; color: var(--heading-color); margin-bottom: .2rem; }
.anomaly-box .ab-msg   { font-size: .78rem; line-height: 1.5; color: var(--body-color); }

/* ── Toasts ───────────────────────────────────────────────────────────────── */
.t-toast-container { position: fixed; top: 1rem; right: 1rem; z-index: 2000; display: flex; flex-direction: column; gap: .5rem; }
.t-toast { display: flex; align-items: center; gap: .55rem; background: var(--card-bg); border: 1px solid var(--card-border); border-left: 4px solid var(--accent); border-radius: 8px; padding: .6rem .9rem; font-size: .82rem; color: var(--heading-color); box-shadow: 0 6px 20px rgba(0,0,0,.12); animation: slideInRight .25s ease; }
.t-toast.toast-error   { border-left-color: #ef4444; }
.t-toast.toast-success { border-left-color: #22c55e; }
.t-toast i { font-size: 1rem; }
.t-toast.toast-error   i { color: #ef4444; }
.t-toast.toast-success i { color: #22c55e; }
@keyframes slideInRight { from{opacity:0;transform:translateX(28px)} to{opacity:1;transform:translateX(0)} }
</style>
@endpush

@section('content')
<div class="cdx-wrap">

    {{-- ── Panel izquierdo: subir documento ──────────────────────────────── --}}
    <div class="t-card">
        <div class="t-card-head">
            <div class="t-card-icon blue"><i class="fas fa-file-export"></i></div>
            <div>
                <h5>Convertir a DocX</h5>
                <p>Pasá .doc, .rtf u .odt a un .docx limpio</p>
            </div>
        </div>
        <div class="t-card-body">

            <div class="fn-label">Subí el documento a convertir</div>
            <div class="drop-zone" id="cdx-drop-zone">
                <input type="file" id="cdx-input" accept=".doc,.docx,.rtf,.odt">
                <i class="fas fa-file-arrow-up dz-icon"></i>
                <div class="dz-title">Arrastrá o hacé clic</div>
                <div class="dz-sub">para seleccionar un documento</div>
                <div><span class="fmt-badge">DOC · DOCX · RTF · ODT — máx. 50 MB</span></div>
            </div>

            <div class="file-preview" id="cdx-file-preview">
                <i class="fp-icon fas fa-file-lines"></i>
                <div class="fp-info">
                    <div class="fp-name" id="cdx-fp-name">—</div>
                    <div class="fp-size" id="cdx-fp-size">—</div>
                </div>
                <button type="button" class="fp-remove" id="cdx-btn-remove"><i class="fas fa-times-circle"></i></button>
            </div>

            <button type="button" class="btn-run" id="cdx-btn-run" disabled>
                <i class="fas fa-wand-magic-sparkles"></i> Convertir a .docx
            </button>

            <div class="t-progress-wrap" id="cdx-progress-wrap">
                <div class="t-progress-label"><span id="cdx-progress-text">Convirtiendo documento…</span></div>
                <div class="t-progress-bar"><div class="t-progress-fill" id="cdx-progress-fill"></div></div>
            </div>

            <div class="hint-box">
                <i class="fas fa-circle-info"></i>
                El formato se detecta leyendo el contenido interno del archivo, no la extensión.
                Un <strong>.docx</strong> que ya sea válido se entrega sin modificaciones.
            </div>

        </div>
    </div>

    {{-- ── Panel derecho: resultado ──────────────────────────────────────── --}}
    <div class="t-card" id="cdx-result-card" style="min-height:60vh;display:flex;flex-direction:column;">
        <div class="t-card-head">
            <div class="t-card-icon green"><i class="fas fa-file-word"></i></div>
            <div>
                <h5 id="cdx-result-title">Resultado</h5>
                <p id="cdx-result-sub">Subí un documento para ver el resultado acá</p>
            </div>
        </div>
        <div class="t-card-body" style="flex:1;display:flex;flex-direction:column;">

            <div class="t-empty" id="cdx-empty-state">
                <i class="fas fa-file-circle-question"></i>
                <p>Subí un documento y el resultado<br>de la conversión aparecerá acá.</p>
            </div>

            {{-- Resultado OK --}}
            <div class="result-panel" id="cdx-result-panel">
                <div class="doc-row src">
                    <i class="dr-icon fas fa-file-lines"></i>
                    <div class="dr-info">
                        <div class="dr-label">Origen</div>
                        <div class="dr-name" id="cdx-src-name">—</div>
                        <div class="dr-meta" id="cdx-src-meta">—</div>
                        <span class="dr-chip" id="cdx-src-chip" style="display:none;"></span>
                    </div>
                </div>

                <div class="arrow-sep"><i class="fas fa-arrow-down"></i></div>

                <div class="doc-row dst">
                    <i class="dr-icon fas fa-file-word"></i>
                    <div class="dr-info">
                        <div class="dr-label">Destino</div>
                        <div class="dr-name" id="cdx-dst-name">—</div>
                        <div class="dr-meta" id="cdx-dst-meta">—</div>
                    </div>
                    <span class="status-badge" id="cdx-dst-badge">—</span>
                </div>

                <ul class="warn-list" id="cdx-warn-list" style="display:none;"></ul>

                <button type="button" class="btn-download-docx" id="cdx-btn-download">
                    <i class="fas fa-file-arrow-down"></i> Descargar .docx
                </button>

                <div class="result-note">
                    <i class="fas fa-circle-check" style="color:#10b981;"></i>
                    El .docx generado está listo para usarse en el Anonimizador.
                </div>
            </div>

            {{-- Resultado con anomalía --}}
            <div class="anomaly-panel" id="cdx-anomaly-panel">
                <div class="anomaly-box">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div>
                        <div class="ab-title">No se pudo generar el .docx</div>
                        <div class="ab-msg" id="cdx-anomaly-msg">—</div>
                    </div>
                </div>

                <div class="doc-row src" id="cdx-anomaly-src" style="display:none;">
                    <i class="dr-icon fas fa-file-circle-xmark"></i>
                    <div class="dr-info">
                        <div class="dr-label">Archivo analizado</div>
                        <div class="dr-name" id="cdx-anomaly-src-name">—</div>
                        <div class="dr-meta" id="cdx-anomaly-src-meta">—</div>
                    </div>
                </div>

                <ul class="warn-list" id="cdx-anomaly-warn-list" style="display:none;"></ul>
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

    function fmtBytes(b) { return b < 1048576 ? (b / 1024).toFixed(1) + ' KB' : (b / 1048576).toFixed(2) + ' MB'; }
    function csrfToken() { return document.querySelector('meta[name="csrf-token"]')?.content ?? ''; }

    const ALLOWED = ['.doc', '.docx', '.rtf', '.odt'];
    const FORMAT_LABELS = {
        doc:  'Word 97-2003 (.doc)',
        docx: 'Word (OOXML, .docx)',
        rtf:  'Texto enriquecido (.rtf)',
        odt:  'OpenDocument (.odt)',
    };

    const toastCont = document.getElementById('toast-container');
    function toast(msg, type = 'info') {
        const icons = { success: 'fa-circle-check', error: 'fa-circle-exclamation', info: 'fa-circle-info' };
        const el = document.createElement('div');
        el.className = `t-toast toast-${type}`;
        el.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i><span>${msg}</span>`;
        toastCont.appendChild(el);
        setTimeout(() => el.remove(), 5000);
    }

    function startProgress(wrap, fill, text, label) {
        wrap.classList.add('show');
        text.textContent = label;
        fill.style.width = '0%';
        let pct = 0;
        return setInterval(() => {
            if (pct < 88) { pct += Math.random() * 3; fill.style.width = Math.min(pct, 88) + '%'; }
        }, 400);
    }
    function stopProgress(wrap, fill, timer) {
        clearInterval(timer);
        fill.style.width = '100%';
        setTimeout(() => { wrap.classList.remove('show'); fill.style.width = '0%'; }, 600);
    }

    const dropZone   = document.getElementById('cdx-drop-zone');
    const input      = document.getElementById('cdx-input');
    const preview    = document.getElementById('cdx-file-preview');
    const fpName     = document.getElementById('cdx-fp-name');
    const fpSize     = document.getElementById('cdx-fp-size');
    const btnRemove  = document.getElementById('cdx-btn-remove');
    const btnRun     = document.getElementById('cdx-btn-run');
    const progWrap   = document.getElementById('cdx-progress-wrap');
    const progFill   = document.getElementById('cdx-progress-fill');
    const progText   = document.getElementById('cdx-progress-text');

    const emptyState   = document.getElementById('cdx-empty-state');
    const resultTitle  = document.getElementById('cdx-result-title');
    const resultSub    = document.getElementById('cdx-result-sub');
    const resultPanel  = document.getElementById('cdx-result-panel');
    const anomalyPanel = document.getElementById('cdx-anomaly-panel');

    let currentFile = null;
    let timer = null;

    function extOf(name) {
        const m = name.toLowerCase().match(/\.[a-z0-9]+$/);
        return m ? m[0] : '';
    }

    function setFile(file) {
        currentFile = file;
        fpName.textContent = file.name;
        fpSize.textContent = fmtBytes(file.size);
        preview.classList.add('show');
        btnRun.disabled = false;
    }
    function clearFile() {
        currentFile = null;
        input.value = '';
        preview.classList.remove('show');
        btnRun.disabled = true;
    }

    function acceptFile(file) {
        if (!file) return;
        if (!ALLOWED.includes(extOf(file.name))) {
            toast('Solo se aceptan documentos .doc, .docx, .rtf u .odt.', 'error');
            return;
        }
        setFile(file);
    }

    input.addEventListener('change', () => acceptFile(input.files[0]));
    btnRemove.addEventListener('click', clearFile);

    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    ['dragleave', 'dragend'].forEach(ev => dropZone.addEventListener(ev, () => dropZone.classList.remove('drag-over')));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const f = e.dataTransfer?.files[0];
        if (!f) return;
        if (!ALLOWED.includes(extOf(f.name))) {
            toast('Solo se aceptan documentos .doc, .docx, .rtf u .odt.', 'error');
            return;
        }
        const dt = new DataTransfer(); dt.items.add(f); input.files = dt.files;
        setFile(f);
    });

    function hidePanels() {
        resultPanel.classList.remove('show');
        anomalyPanel.classList.remove('show');
    }

    function renderWarnings(listEl, warnings) {
        if (!warnings || !warnings.length) { listEl.style.display = 'none'; listEl.innerHTML = ''; return; }
        listEl.innerHTML = warnings
            .map(w => `<li><i class="fas fa-triangle-exclamation"></i><span>${w}</span></li>`)
            .join('');
        listEl.style.display = 'flex';
    }

    function renderSource(src) {
        const label = src.format_label || FORMAT_LABELS[src.detected_format] || 'Formato desconocido';
        document.getElementById('cdx-src-name').textContent = src.filename || '—';
        document.getElementById('cdx-src-meta').textContent =
            `${label} · ${fmtBytes(src.size_bytes || 0)}`;
        const chip = document.getElementById('cdx-src-chip');
        if (src.extension_mismatch) {
            chip.textContent = 'Extensión ≠ contenido real';
            chip.style.display = 'inline-block';
        } else {
            chip.style.display = 'none';
        }
    }

    function showResult(data) {
        hidePanels();
        emptyState.style.display = 'none';

        renderSource(data.source || {});

        const res = data.result || {};
        const dstName = res.filename || (currentFile.name.replace(/\.[^.]+$/, '') + '.docx');
        document.getElementById('cdx-dst-name').textContent = dstName;
        document.getElementById('cdx-dst-meta').textContent = fmtBytes(res.size_bytes || 0);

        const badge = document.getElementById('cdx-dst-badge');
        if (res.status === 'passthrough') {
            badge.className = 'status-badge passthrough';
            badge.textContent = 'Sin cambios';
        } else {
            badge.className = 'status-badge converted';
            badge.textContent = 'Convertido';
        }

        renderWarnings(document.getElementById('cdx-warn-list'), data.warnings);

        const btnDownload = document.getElementById('cdx-btn-download');
        btnDownload.onclick = () => {
            const a = document.createElement('a');
            a.href = data.download_url;
            a.download = dstName;
            document.body.appendChild(a);
            a.click();
            a.remove();
        };

        resultPanel.classList.add('show');
        resultTitle.textContent = res.status === 'passthrough' ? 'Documento válido' : 'Documento convertido';
        resultSub.textContent   = 'Revisá el detalle y descargá el .docx';
        toast(res.status === 'passthrough' ? 'El archivo ya era un .docx válido.' : '¡Documento convertido a .docx!', 'success');
    }

    function showAnomaly(data) {
        hidePanels();
        emptyState.style.display = 'none';

        document.getElementById('cdx-anomaly-msg').textContent =
            data.message || 'El contenido del documento no pudo procesarse.';

        const srcRow = document.getElementById('cdx-anomaly-src');
        if (data.source && data.source.filename) {
            const src = data.source;
            const label = src.format_label || FORMAT_LABELS[src.detected_format] || 'Formato desconocido';
            document.getElementById('cdx-anomaly-src-name').textContent = src.filename;
            document.getElementById('cdx-anomaly-src-meta').textContent =
                `${label} · ${fmtBytes(src.size_bytes || 0)}`;
            srcRow.style.display = 'flex';
        } else {
            srcRow.style.display = 'none';
        }

        renderWarnings(document.getElementById('cdx-anomaly-warn-list'), data.warnings);

        anomalyPanel.classList.add('show');
        resultTitle.textContent = 'No se pudo convertir';
        resultSub.textContent   = 'Revisá el detalle para saber cómo seguir';
        toast('El documento no pudo convertirse. Revisá el panel de resultados.', 'error');
    }

    btnRun.addEventListener('click', async () => {
        if (!currentFile) return;
        btnRun.disabled = true;
        timer = startProgress(progWrap, progFill, progText, 'Convirtiendo documento…');
        hidePanels();
        emptyState.style.display = 'none';

        const fd = new FormData();
        fd.append('_token', csrfToken());
        fd.append('file', currentFile);

        try {
            const res  = await fetch('{{ route("convertir-docx.convert") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                body: fd,
            });
            const data = await res.json().catch(() => ({}));

            if (res.status === 422 && data.errors) {
                const first = Object.values(data.errors)[0]?.[0] || 'Archivo inválido.';
                toast(first, 'error');
                return;
            }
            if (!res.ok && data.ok !== false) {
                toast(data.message || 'Error al convertir el documento.', 'error');
                return;
            }

            if (data.ok === false) {
                showAnomaly(data);
            } else {
                showResult(data);
            }
        } catch (err) {
            toast('Error de red: ' + err.message, 'error');
        } finally {
            stopProgress(progWrap, progFill, timer);
            btnRun.disabled = false;
        }
    });
})();
</script>
@endpush
