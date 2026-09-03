@extends('layouts.dashboard')

@section('title', 'PDF Tools — ' . config('app.name', 'Alfa'))
@section('page-title', 'PDF Tools')
@section('breadcrumb', 'PDF Tools')

@push('styles')
<style>
/* ── Layout principal ─────────────────────────────────────────────────────── */
.ptp-wrap {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 1.25rem;
    align-items: stretch;
}
@media (min-width: 961px) {
    .ptp-wrap { min-height: calc(100vh - var(--topbar-h) - 7rem); }
    .ptp-wrap > .t-card { display: flex; flex-direction: column; }
    .ptp-wrap > .t-card > .t-card-body { flex: 1; }
}
@media (max-width: 960px) {
    .ptp-wrap { grid-template-columns: 1fr; align-items: start; }
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
.t-card-icon.purple { background: linear-gradient(135deg,#7c3aed,#a855f7); box-shadow: 0 3px 10px rgba(124,58,237,.3); }
.t-card-head h5 { margin: 0; font-size: .92rem; font-weight: 700; color: var(--heading-color); line-height: 1.25; }
.t-card-head p  { margin: 0; font-size: .73rem; color: var(--muted-color); }
.t-card-body { padding: 1.1rem; }

/* ── Selector de función (paso 1) ─────────────────────────────────────────── */
.fn-label {
    font-size: .72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: var(--muted-color); margin-bottom: .65rem;
}
.fn-buttons { display: flex; flex-direction: column; gap: .55rem; margin-bottom: 1rem; }
.btn-fn {
    width: 100%; display: flex; align-items: center; gap: .6rem;
    padding: .68rem .9rem; border-radius: 9px; cursor: pointer;
    font-size: .85rem; font-weight: 600; text-align: left;
    background: var(--input-bg); color: var(--heading-color);
    border: 1px solid var(--input-border);
    transition: border-color .18s, background .18s, transform .12s;
}
.btn-fn:hover { transform: translateY(-1px); }
.btn-fn .fn-icon {
    width: 30px; height: 30px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; flex-shrink: 0; color: #fff;
}
.btn-fn .fn-text { flex: 1; }
.btn-fn .fn-text small { display: block; font-size: .68rem; font-weight: 400; opacity: .8; }
.btn-fn[data-fn="ocr"]      .fn-icon { background: linear-gradient(135deg,#2563eb,#1d4ed8); }
.btn-fn[data-fn="compress"] .fn-icon { background: linear-gradient(135deg,#d97706,#b45309); }
.btn-fn[data-fn="merge"]    .fn-icon { background: linear-gradient(135deg,#7c3aed,#6d28d9); }
.btn-fn.active[data-fn="ocr"]      { border-color: color-mix(in srgb,#2563eb 45%,transparent); background: color-mix(in srgb,#2563eb 10%,var(--card-bg)); }
.btn-fn.active[data-fn="compress"] { border-color: color-mix(in srgb,#d97706 45%,transparent); background: color-mix(in srgb,#d97706 10%,var(--card-bg)); }
.btn-fn.active[data-fn="merge"]    { border-color: color-mix(in srgb,#7c3aed 45%,transparent); background: color-mix(in srgb,#7c3aed 10%,var(--card-bg)); }

/* ── Paso 2: paneles de acción ─────────────────────────────────────────────── */
.fn-panel { display: none; padding-top: 1rem; border-top: 1px solid var(--card-border); }
.fn-panel.visible { display: block; }

.drop-zone {
    border: 2px dashed var(--input-border);
    border-radius: 12px; padding: 1.6rem 1.1rem; text-align: center;
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
.fp-icon  { font-size: 1.3rem; color: #dc2626; flex-shrink: 0; }
.fp-info  { flex: 1; min-width: 0; }
.fp-name  { font-size: .8rem; font-weight: 600; color: var(--heading-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fp-size  { font-size: .71rem; color: var(--muted-color); }
.fp-remove { background: none; border: none; color: #94a3b8; font-size: 1rem; cursor: pointer; padding: 0 .2rem; flex-shrink: 0; }
.fp-remove:hover { color: #ef4444; }

.compress-opts { margin-top: .8rem; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 8px; padding: .6rem .75rem; }
.compress-opts label { display: block; font-size: .71rem; font-weight: 600; color: var(--muted-color); margin-bottom: .3rem; text-transform: uppercase; letter-spacing: .04em; }
.compress-opts select { width: 100%; padding: .38rem .6rem; font-size: .8rem; color: var(--input-color); background: var(--card-bg); border: 1px solid var(--input-border); border-radius: 6px; outline: none; cursor: pointer; }

.btn-run {
    width: 100%; margin-top: .8rem; display: flex; align-items: center; justify-content: center; gap: .5rem;
    padding: .62rem .9rem; border-radius: 9px; border: none; cursor: pointer;
    font-size: .85rem; font-weight: 700; color: #fff; transition: opacity .18s, transform .12s;
}
.btn-run:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
.btn-run:disabled { opacity: .45; cursor: not-allowed; }
.btn-run.ocr      { background: linear-gradient(135deg,#2563eb,#1d4ed8); }
.btn-run.compress { background: linear-gradient(135deg,#d97706,#b45309); }
.btn-run.merge    { background: linear-gradient(135deg,#7c3aed,#6d28d9); }

/* ── Lista reordenable de "Unir PDF" ──────────────────────────────────────── */
.merge-list { list-style: none; margin: .8rem 0 0; padding: 0; display: flex; flex-direction: column; gap: .4rem; }
.merge-item {
    display: flex; align-items: center; gap: .5rem;
    background: var(--input-bg); border: 1px solid var(--input-border);
    border-radius: 8px; padding: .45rem .6rem;
    border-top: 2px solid transparent; border-bottom: 2px solid transparent;
    transition: border-color .12s, background .12s;
}
.merge-item.dragging { opacity: .4; }
.merge-item.drag-over-top    { border-top-color: var(--accent); }
.merge-item.drag-over-bottom { border-bottom-color: var(--accent); }
.merge-item .mi-handle { color: var(--muted-color); font-size: .85rem; flex-shrink: 0; cursor: grab; touch-action: none; }
.merge-item .mi-handle:active { cursor: grabbing; }
.merge-item .mi-order  {
    width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
    background: color-mix(in srgb,#7c3aed 18%,var(--card-bg)); color: #7c3aed;
    font-size: .7rem; font-weight: 700; display: flex; align-items: center; justify-content: center;
}
.merge-item .mi-icon { color: #dc2626; font-size: 1rem; flex-shrink: 0; }
.merge-item .mi-info  { flex: 1; min-width: 0; }
.merge-item .mi-name  { font-size: .78rem; font-weight: 600; color: var(--heading-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.merge-item .mi-size  { font-size: .68rem; color: var(--muted-color); }
.merge-item .mi-actions { display: flex; flex-direction: column; flex-shrink: 0; }
.merge-item .mi-move { background: none; border: none; color: #94a3b8; font-size: .68rem; line-height: 1; cursor: pointer; padding: .14rem .25rem; }
.merge-item .mi-move:hover:not(:disabled) { color: var(--accent); }
.merge-item .mi-move:disabled { opacity: .25; cursor: not-allowed; }
.merge-item .mi-remove { background: none; border: none; color: #94a3b8; font-size: .95rem; cursor: pointer; flex-shrink: 0; }
.merge-item .mi-remove:hover { color: #ef4444; }
.merge-add-more {
    margin-top: .6rem; width: 100%; padding: .5rem; text-align: center;
    border: 1px dashed var(--input-border); border-radius: 8px; cursor: pointer;
    font-size: .78rem; color: var(--muted-color); background: transparent; position: relative;
}
.merge-add-more:hover { border-color: var(--accent); color: var(--accent); }
.merge-add-more input { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.merge-empty-hint { font-size: .78rem; color: var(--muted-color); margin-top: .6rem; text-align: center; }

/* ── Barra de progreso ─────────────────────────────────────────────────────── */
.t-progress-wrap { display: none; margin-top: .85rem; }
.t-progress-wrap.show { display: block; }
.t-progress-label { font-size: .74rem; color: var(--muted-color); margin-bottom: .28rem; display: flex; justify-content: space-between; }
.t-progress-bar { height: 5px; border-radius: 99px; background: var(--badge-light-bg); overflow: hidden; }
.t-progress-fill { height: 100%; width: 0%; border-radius: 99px; background: linear-gradient(90deg, var(--accent), var(--accent2)); transition: width .4s; animation: pgpulse 1.4s ease-in-out infinite; }
.t-progress-fill.amber  { background: linear-gradient(90deg,#d97706,#f59e0b); }
.t-progress-fill.purple { background: linear-gradient(90deg,#7c3aed,#a855f7); }
@keyframes pgpulse { 0%,100%{opacity:1} 50%{opacity:.55} }

/* ── Panel de resultados (derecha) ─────────────────────────────────────────── */
.t-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; text-align: center; color: var(--muted-color); gap: .6rem; padding: 2rem 1rem; }
.t-empty i { font-size: 2.4rem; opacity: .35; }
.t-empty p { margin: 0; font-size: .85rem; }

.result-panel { display: none; flex: 1; flex-direction: column; }
.result-panel.show { display: flex; }

.t-result-meta { display: flex; gap: .8rem; flex-wrap: wrap; padding: .5rem .85rem; background: var(--alert-success-bg); border: 1px solid var(--alert-success-color); border-radius: 9px; margin-bottom: .75rem; font-size: .78rem; color: var(--heading-color); }
.t-result-meta span { display: flex; align-items: center; gap: .35rem; }

.t-textarea { flex: 1; min-height: 260px; width: 100%; resize: vertical; padding: .8rem; font-size: .82rem; font-family: ui-monospace, monospace; line-height: 1.5; color: var(--input-color); background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 9px; outline: none; }

.t-result-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .8rem; }
.t-btn { display: flex; align-items: center; gap: .4rem; padding: .5rem .85rem; border-radius: 8px; border: 1px solid var(--input-border); background: var(--input-bg); color: var(--heading-color); font-size: .78rem; font-weight: 600; cursor: pointer; }
.t-btn:hover { border-color: var(--accent); color: var(--accent); }
.t-btn-download { background: linear-gradient(135deg, var(--accent), var(--accent2)); color: #fff; border: none; }
.t-btn-download:hover { color: #fff; opacity: .9; }

.compress-result { display: none; flex: 1; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: .3rem; padding: 1.5rem; }
.compress-result.show { display: flex; }
.cr-icon { font-size: 2.4rem; }
.cr-title { font-size: 1rem; font-weight: 700; color: var(--heading-color); }
.cr-stats { display: flex; gap: 1.4rem; margin: 1rem 0; }
.cr-stat { font-size: .78rem; color: var(--muted-color); text-align: center; }
.cr-stat strong { display: block; font-size: 1.05rem; color: var(--heading-color); }
.cr-reduction strong { color: #10b981; }
.btn-download-pdf {
    display: inline-flex; align-items: center; gap: .5rem; margin-top: .5rem;
    padding: .6rem 1.2rem; border-radius: 9px; text-decoration: none;
    background: var(--badge-light-bg); color: var(--heading-color);
    border: 1px solid var(--badge-light-border); font-weight: 700; font-size: .85rem;
}
.btn-download-pdf:hover { background: color-mix(in srgb,#dc2626 12%,var(--badge-light-bg)); color: #dc2626; }

/* ── Panel índice (Unir PDF) ───────────────────────────────────────────────── */
.merge-result { display: none; flex: 1; flex-direction: column; }
.merge-result.show { display: flex; }
.merge-index-table { width: 100%; border-collapse: collapse; font-size: .8rem; margin-top: .4rem; }
.merge-index-table th { text-align: left; font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: var(--muted-color); padding: .4rem .5rem; border-bottom: 1px solid var(--card-border); }
.merge-index-table td { padding: .5rem; border-bottom: 1px solid var(--card-border); color: var(--heading-color); }
.merge-index-table tr:last-child td { border-bottom: none; }
.merge-index-table .mit-num { width: 34px; color: var(--muted-color); }
.merge-index-table .mit-pages, .merge-index-table .mit-fojas { white-space: nowrap; color: var(--muted-color); text-align: right; }

/* ── Toasts ────────────────────────────────────────────────────────────────── */
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
<div class="ptp-wrap">

    {{-- ── Panel izquierdo: 1. elegir función → 2. subir PDF(s) ────────────── --}}
    <div class="t-card">
        <div class="t-card-head">
            <div class="t-card-icon purple"><i class="fas fa-layer-group"></i></div>
            <div>
                <h5>PDF Tools</h5>
                <p>Elegí una función y luego subí tu(s) PDF</p>
            </div>
        </div>
        <div class="t-card-body">

            <div class="fn-label">1. Elegí una función</div>
            <div class="fn-buttons">
                <button type="button" class="btn-fn" data-fn="ocr">
                    <span class="fn-icon"><i class="fas fa-file-image"></i></span>
                    <span class="fn-text">Extracción OCR<small>Extraer el texto de un PDF escaneado</small></span>
                    <i class="fas fa-chevron-right" style="opacity:.4;font-size:.75rem;"></i>
                </button>
                <button type="button" class="btn-fn" data-fn="compress">
                    <span class="fn-icon"><i class="fas fa-file-zipper"></i></span>
                    <span class="fn-text">Comprimir PDF<small>Reducir el tamaño de un PDF</small></span>
                    <i class="fas fa-chevron-right" style="opacity:.4;font-size:.75rem;"></i>
                </button>
                <button type="button" class="btn-fn" data-fn="merge">
                    <span class="fn-icon"><i class="fas fa-object-group"></i></span>
                    <span class="fn-text">Unir PDF<small>Combinar varios PDF en uno solo, con índice</small></span>
                    <i class="fas fa-chevron-right" style="opacity:.4;font-size:.75rem;"></i>
                </button>
            </div>

            {{-- ── Panel OCR ────────────────────────────────────────────────── --}}
            <div class="fn-panel" id="panel-ocr-upload">
                <div class="fn-label">2. Subí el PDF a procesar</div>
                <div class="drop-zone" id="ocr-drop-zone">
                    <input type="file" id="ocr-input" accept=".pdf,application/pdf">
                    <i class="fas fa-file-pdf dz-icon" style="color:#2563eb;"></i>
                    <div class="dz-title">Arrastrá o hacé clic</div>
                    <div class="dz-sub">para seleccionar un PDF</div>
                    <div><span class="fmt-badge" style="background:color-mix(in srgb,#2563eb 12%,var(--badge-light-bg));border-color:color-mix(in srgb,#2563eb 25%,transparent);color:#2563eb;">PDF · máx. 50 MB</span></div>
                </div>
                <div class="file-preview" id="ocr-file-preview">
                    <i class="fp-icon fas fa-file-pdf"></i>
                    <div class="fp-info">
                        <div class="fp-name" id="ocr-fp-name">—</div>
                        <div class="fp-size" id="ocr-fp-size">—</div>
                    </div>
                    <button type="button" class="fp-remove" id="ocr-btn-remove"><i class="fas fa-times-circle"></i></button>
                </div>
                <button type="button" class="btn-run ocr" id="btn-ocr-run" disabled>
                    <i class="fas fa-magnifying-glass"></i> Extraer texto
                </button>
                <div class="t-progress-wrap" id="ocr-progress-wrap">
                    <div class="t-progress-label"><span id="ocr-progress-text">Procesando…</span></div>
                    <div class="t-progress-bar"><div class="t-progress-fill" id="ocr-progress-fill"></div></div>
                </div>
            </div>

            {{-- ── Panel Comprimir ──────────────────────────────────────────── --}}
            <div class="fn-panel" id="panel-compress-upload">
                <div class="fn-label">2. Subí el PDF a comprimir</div>
                <div class="drop-zone" id="compress-drop-zone">
                    <input type="file" id="compress-input" accept=".pdf,application/pdf">
                    <i class="fas fa-file-pdf dz-icon" style="color:#d97706;"></i>
                    <div class="dz-title">Arrastrá o hacé clic</div>
                    <div class="dz-sub">para seleccionar un PDF</div>
                    <div><span class="fmt-badge" style="background:color-mix(in srgb,#d97706 12%,var(--badge-light-bg));border-color:color-mix(in srgb,#d97706 25%,transparent);color:#d97706;">PDF · máx. 100 MB</span></div>
                </div>
                <div class="file-preview" id="compress-file-preview">
                    <i class="fp-icon fas fa-file-pdf"></i>
                    <div class="fp-info">
                        <div class="fp-name" id="compress-fp-name">—</div>
                        <div class="fp-size" id="compress-fp-size">—</div>
                    </div>
                    <button type="button" class="fp-remove" id="compress-btn-remove"><i class="fas fa-times-circle"></i></button>
                </div>
                <div class="compress-opts">
                    <label>Nivel de compresión</label>
                    <select id="compress-level">
                        @foreach($levels as $key => $info)
                        <option value="{{ $key }}" {{ $key === 'ebook' ? 'selected' : '' }}>
                            {{ $info['label'] }} — {{ $info['desc'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn-run compress" id="btn-compress-run" disabled>
                    <i class="fas fa-rocket"></i> Comprimir ahora
                </button>
                <div class="t-progress-wrap" id="compress-progress-wrap">
                    <div class="t-progress-label"><span id="compress-progress-text">Comprimiendo…</span></div>
                    <div class="t-progress-bar"><div class="t-progress-fill amber" id="compress-progress-fill"></div></div>
                </div>
            </div>

            {{-- ── Panel Unir PDF ───────────────────────────────────────────── --}}
            <div class="fn-panel" id="panel-merge-upload">
                <div class="fn-label">2. Subí los PDF a unir (arrastrá del ícono ⣿ o usá las flechas para ordenar)</div>
                <div class="drop-zone" id="merge-drop-zone">
                    <input type="file" id="merge-input" accept=".pdf,application/pdf" multiple>
                    <i class="fas fa-file-pdf dz-icon" style="color:#7c3aed;"></i>
                    <div class="dz-title">Arrastrá o hacé clic</div>
                    <div class="dz-sub">para seleccionar uno o varios PDF</div>
                    <div><span class="fmt-badge" style="background:color-mix(in srgb,#7c3aed 12%,var(--badge-light-bg));border-color:color-mix(in srgb,#7c3aed 25%,transparent);color:#7c3aed;">PDF · máx. 50 MB c/u · hasta 30 archivos</span></div>
                </div>

                <div class="merge-empty-hint" id="merge-empty-hint">Todavía no agregaste ningún PDF.</div>
                <ul class="merge-list" id="merge-list"></ul>

                <label class="merge-add-more" id="merge-add-more" style="display:none;">
                    <i class="fas fa-plus"></i> Agregar más archivos
                    <input type="file" id="merge-add-input" accept=".pdf,application/pdf" multiple>
                </label>

                <button type="button" class="btn-run merge" id="btn-merge-run" disabled>
                    <i class="fas fa-object-group"></i> Unir PDF
                </button>
                <div class="t-progress-wrap" id="merge-progress-wrap">
                    <div class="t-progress-label"><span id="merge-progress-text">Uniendo documentos…</span></div>
                    <div class="t-progress-bar"><div class="t-progress-fill purple" id="merge-progress-fill"></div></div>
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
                <p id="result-card-sub">Elegí una función a la izquierda para empezar</p>
            </div>
        </div>
        <div class="t-card-body" style="flex:1;display:flex;flex-direction:column;">

            <div class="t-empty" id="empty-state">
                <i class="fas fa-file-circle-question"></i>
                <p>Elegí una función, subí tu(s) PDF<br>y el resultado va a aparecer acá.</p>
            </div>

            {{-- Resultado OCR --}}
            <div class="result-panel" id="panel-ocr-result">
                <div class="t-result-meta" id="ocr-meta"></div>
                <textarea class="t-textarea" id="ocr-textarea" placeholder="El texto OCR aparecerá aquí…"></textarea>
                <div class="t-result-actions">
                    <button type="button" class="t-btn" id="btn-copy"><i class="fas fa-copy"></i> Copiar texto</button>
                    <button type="button" class="t-btn t-btn-download" id="btn-download-txt"><i class="fas fa-file-arrow-down"></i> Descargar .txt</button>
                </div>
            </div>

            {{-- Resultado Comprimir --}}
            <div class="compress-result" id="panel-compress-result">
                <i class="fas fa-file-pdf cr-icon" style="color:#dc2626;"></i>
                <div class="cr-title">¡Compresión completada!</div>
                <div class="cr-stats" id="compress-stats"></div>
                <a href="#" class="btn-download-pdf" id="btn-download-pdf"><i class="fas fa-file-pdf"></i> Descargar PDF comprimido</a>
            </div>

            {{-- Resultado Unir PDF: índice + descarga --}}
            <div class="merge-result" id="panel-merge-result">
                <div class="t-result-meta" id="merge-meta"></div>
                <table class="merge-index-table">
                    <thead>
                        <tr><th class="mit-num">#</th><th>Documento</th><th class="mit-pages">Páginas</th><th class="mit-fojas">Fojas</th></tr>
                    </thead>
                    <tbody id="merge-index-body"></tbody>
                </table>
                <div class="t-result-actions">
                    <a href="#" class="btn-download-pdf" id="btn-download-merge"><i class="fas fa-file-pdf"></i> Descargar PDF unido</a>
                </div>
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

    // ── Helpers comunes ──────────────────────────────────────────────────────
    function fmtBytes(b) { return b < 1048576 ? (b / 1024).toFixed(1) + ' KB' : (b / 1048576).toFixed(2) + ' MB'; }
    function csrfToken() { return document.querySelector('meta[name="csrf-token"]')?.content ?? ''; }

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
        const timer = setInterval(() => {
            if (pct < 88) { pct += Math.random() * 3; fill.style.width = Math.min(pct, 88) + '%'; }
        }, 400);
        return timer;
    }
    function stopProgress(wrap, fill, timer) {
        clearInterval(timer);
        fill.style.width = '100%';
        setTimeout(() => { wrap.classList.remove('show'); fill.style.width = '0%'; }, 600);
    }

    const emptyState      = document.getElementById('empty-state');
    const resultCardTitle = document.getElementById('result-card-title');
    const resultCardSub   = document.getElementById('result-card-sub');
    const resultPanels     = [
        document.getElementById('panel-ocr-result'),
        document.getElementById('panel-compress-result'),
        document.getElementById('panel-merge-result'),
    ];

    function hideAllResults() {
        resultPanels.forEach(p => p.classList.remove('show'));
    }
    function showEmptyResult(title, sub) {
        hideAllResults();
        emptyState.style.display = '';
        resultCardTitle.textContent = title || 'Resultado';
        resultCardSub.textContent   = sub   || 'El resultado del proceso aparecerá aquí';
    }
    showEmptyResult();

    // ── Paso 1: selector de función ──────────────────────────────────────────
    const fnButtons = document.querySelectorAll('.btn-fn');
    const fnPanels  = {
        ocr:      document.getElementById('panel-ocr-upload'),
        compress: document.getElementById('panel-compress-upload'),
        merge:    document.getElementById('panel-merge-upload'),
    };

    fnButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const fn = btn.dataset.fn;
            fnButtons.forEach(b => b.classList.toggle('active', b === btn));
            Object.entries(fnPanels).forEach(([key, panel]) => panel.classList.toggle('visible', key === fn));
            hideAllResults();
            emptyState.style.display = 'none';
            resultCardTitle.textContent = btn.querySelector('.fn-text').firstChild.textContent.trim();
            resultCardSub.textContent   = 'Subí tu(s) PDF y ejecutá la acción para ver el resultado acá';
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // OCR
    // ═══════════════════════════════════════════════════════════════════════
    (function initOcr() {
        const dropZone   = document.getElementById('ocr-drop-zone');
        const input      = document.getElementById('ocr-input');
        const preview    = document.getElementById('ocr-file-preview');
        const fpName     = document.getElementById('ocr-fp-name');
        const fpSize     = document.getElementById('ocr-fp-size');
        const btnRemove  = document.getElementById('ocr-btn-remove');
        const btnRun     = document.getElementById('btn-ocr-run');
        const progWrap   = document.getElementById('ocr-progress-wrap');
        const progFill   = document.getElementById('ocr-progress-fill');
        const progText   = document.getElementById('ocr-progress-text');
        const resultPanel= document.getElementById('panel-ocr-result');
        const meta       = document.getElementById('ocr-meta');
        const textarea   = document.getElementById('ocr-textarea');
        const btnCopy    = document.getElementById('btn-copy');
        const btnDownload= document.getElementById('btn-download-txt');

        let currentFile = null;
        let txtName     = 'texto-extraido.txt';
        let timer       = null;

        function setFile(file) {
            currentFile = file;
            txtName = file.name.replace(/\.pdf$/i, '') + '.txt';
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

        input.addEventListener('change', () => { if (input.files[0]) setFile(input.files[0]); });
        btnRemove.addEventListener('click', clearFile);

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
            const dt = new DataTransfer(); dt.items.add(f); input.files = dt.files;
            setFile(f);
        });

        btnRun.addEventListener('click', async () => {
            if (!currentFile) return;
            btnRun.disabled = true;
            timer = startProgress(progWrap, progFill, progText, 'Procesando OCR…');
            hideAllResults();
            emptyState.style.display = 'none';

            const fd = new FormData();
            fd.append('_token', csrfToken());
            fd.append('pdf', currentFile);

            try {
                const res  = await fetch('{{ route("pdf-tools-pro.ocr") }}', { method: 'POST', body: fd });
                const data = await res.json();
                if (!res.ok || data.error) { toast(data.error || 'Error al procesar OCR.', 'error'); return; }

                textarea.value = data.text;
                meta.innerHTML = `
                    <span><i class="fas fa-file-lines"></i> ${data.pages} pág.</span>
                    <span><i class="fas fa-font"></i> ${data.chars.toLocaleString()} caracteres</span>
                    <span><i class="fas fa-align-left"></i> ${data.text.split('\n').length.toLocaleString()} líneas</span>
                `;
                hideAllResults();
                resultPanel.classList.add('show');
                resultCardTitle.textContent = 'Texto extraído (OCR)';
                resultCardSub.textContent   = 'Podés editar el resultado antes de copiarlo o descargarlo';
                toast('Texto extraído correctamente.', 'success');
            } catch (err) {
                toast('Error de red: ' + err.message, 'error');
            } finally {
                stopProgress(progWrap, progFill, timer);
                btnRun.disabled = false;
            }
        });

        btnCopy.addEventListener('click', async () => {
            try { await navigator.clipboard.writeText(textarea.value); toast('Texto copiado al portapapeles.', 'success'); }
            catch { textarea.select(); document.execCommand('copy'); toast('Texto copiado.', 'success'); }
        });

        btnDownload.addEventListener('click', () => {
            const blob = new Blob([textarea.value], { type: 'text/plain;charset=utf-8' });
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href = url; a.download = txtName; a.click();
            URL.revokeObjectURL(url);
        });
    })();

    // ═══════════════════════════════════════════════════════════════════════
    // Comprimir
    // ═══════════════════════════════════════════════════════════════════════
    (function initCompress() {
        const dropZone   = document.getElementById('compress-drop-zone');
        const input      = document.getElementById('compress-input');
        const preview    = document.getElementById('compress-file-preview');
        const fpName     = document.getElementById('compress-fp-name');
        const fpSize     = document.getElementById('compress-fp-size');
        const btnRemove  = document.getElementById('compress-btn-remove');
        const level      = document.getElementById('compress-level');
        const btnRun     = document.getElementById('btn-compress-run');
        const progWrap   = document.getElementById('compress-progress-wrap');
        const progFill   = document.getElementById('compress-progress-fill');
        const progText   = document.getElementById('compress-progress-text');
        const resultPanel= document.getElementById('panel-compress-result');
        const stats      = document.getElementById('compress-stats');
        const btnDownload= document.getElementById('btn-download-pdf');

        let currentFile = null;
        let timer       = null;

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

        input.addEventListener('change', () => { if (input.files[0]) setFile(input.files[0]); });
        btnRemove.addEventListener('click', clearFile);

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
            const dt = new DataTransfer(); dt.items.add(f); input.files = dt.files;
            setFile(f);
        });

        btnRun.addEventListener('click', async () => {
            if (!currentFile) return;
            btnRun.disabled = true;
            timer = startProgress(progWrap, progFill, progText, 'Comprimiendo PDF…');
            hideAllResults();
            emptyState.style.display = 'none';

            const fd = new FormData();
            fd.append('_token', csrfToken());
            fd.append('pdf', currentFile);
            fd.append('level', level.value);

            try {
                const res = await fetch('{{ route("pdf-tools-pro.compress") }}', { method: 'POST', body: fd });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({ error: 'Error desconocido.' }));
                    toast(err.error || 'Error al comprimir.', 'error');
                    return;
                }

                const origSize  = parseInt(res.headers.get('X-Original-Size')   || '0');
                const compSize  = parseInt(res.headers.get('X-Compressed-Size') || '0');
                const reduction = parseFloat(res.headers.get('X-Reduction-Percent') || '0');
                const blob = await res.blob();

                stats.innerHTML = `
                    <div class="cr-stat"><strong>${origSize > 0 ? (origSize/1024).toFixed(0) + ' KB' : '—'}</strong>Tamaño original</div>
                    <div class="cr-stat"><strong>${compSize > 0 ? (compSize/1024).toFixed(0) + ' KB' : '—'}</strong>Tamaño final</div>
                    <div class="cr-stat cr-reduction"><strong>${reduction > 0 ? reduction + '%' : '—'}</strong>Reducción</div>
                `;

                const url = URL.createObjectURL(blob);
                btnDownload.href = url;
                btnDownload.download = `${currentFile.name.replace(/\.pdf$/i, '')}_${level.value}_comprimido.pdf`;

                hideAllResults();
                resultPanel.classList.add('show');
                resultCardTitle.textContent = 'PDF comprimido';
                resultCardSub.textContent   = 'Proceso completado — descargá el resultado';
                toast('¡PDF comprimido correctamente!', 'success');
            } catch (err) {
                toast('Error de red: ' + err.message, 'error');
            } finally {
                stopProgress(progWrap, progFill, timer);
                btnRun.disabled = false;
            }
        });
    })();

    // ═══════════════════════════════════════════════════════════════════════
    // Unir PDF — cola reordenable (drag & drop) + fusión
    // ═══════════════════════════════════════════════════════════════════════
    (function initMerge() {
        const dropZone    = document.getElementById('merge-drop-zone');
        const input       = document.getElementById('merge-input');
        const addMoreWrap = document.getElementById('merge-add-more');
        const addMoreInput= document.getElementById('merge-add-input');
        const listEl      = document.getElementById('merge-list');
        const emptyHint   = document.getElementById('merge-empty-hint');
        const btnRun      = document.getElementById('btn-merge-run');
        const progWrap    = document.getElementById('merge-progress-wrap');
        const progFill    = document.getElementById('merge-progress-fill');
        const progText    = document.getElementById('merge-progress-text');
        const resultPanel = document.getElementById('panel-merge-result');
        const meta        = document.getElementById('merge-meta');
        const indexBody   = document.getElementById('merge-index-body');
        const btnDownload = document.getElementById('btn-download-merge');

        let queue = [];      // File[] en el orden final de fusión
        let dragIndex = null;
        let timer = null;

        function addFiles(fileList) {
            const rejected = [];
            Array.from(fileList).forEach(f => {
                const isPdf = f.type === 'application/pdf' || f.name.toLowerCase().endsWith('.pdf');
                if (!isPdf) { rejected.push(f.name); return; }
                queue.push(f);
            });
            if (rejected.length) toast(`Se ignoraron ${rejected.length} archivo(s) que no son PDF.`, 'error');
            render();
        }

        function removeAt(index) {
            queue.splice(index, 1);
            render();
        }

        function moveTo(from, to) {
            if (from === to || to < 0 || to >= queue.length) return;
            const [moved] = queue.splice(from, 1);
            queue.splice(to, 0, moved);
            render();
        }

        function clearDragOverMarks() {
            listEl.querySelectorAll('.drag-over-top, .drag-over-bottom').forEach(el => {
                el.classList.remove('drag-over-top', 'drag-over-bottom');
            });
        }

        function render() {
            listEl.innerHTML = '';
            emptyHint.style.display   = queue.length ? 'none' : '';
            addMoreWrap.style.display = queue.length ? '' : 'none';
            btnRun.disabled = queue.length < 2;

            queue.forEach((file, index) => {
                const li = document.createElement('li');
                li.className = 'merge-item';
                li.draggable = false;
                li.dataset.index = index;
                li.innerHTML = `
                    <i class="fas fa-grip-vertical mi-handle" title="Arrastrar para reordenar"></i>
                    <span class="mi-order">${index + 1}</span>
                    <i class="fas fa-file-pdf mi-icon"></i>
                    <div class="mi-info">
                        <div class="mi-name">${file.name}</div>
                        <div class="mi-size">${fmtBytes(file.size)}</div>
                    </div>
                    <div class="mi-actions">
                        <button type="button" class="mi-move mi-move-up" title="Subir"${index === 0 ? ' disabled' : ''}><i class="fas fa-chevron-up"></i></button>
                        <button type="button" class="mi-move mi-move-down" title="Bajar"${index === queue.length - 1 ? ' disabled' : ''}><i class="fas fa-chevron-down"></i></button>
                    </div>
                    <button type="button" class="mi-remove" title="Quitar"><i class="fas fa-times-circle"></i></button>
                `;
                li.querySelector('.mi-move-up').addEventListener('click', () => moveTo(index, index - 1));
                li.querySelector('.mi-move-down').addEventListener('click', () => moveTo(index, index + 1));
                li.querySelector('.mi-remove').addEventListener('click', () => removeAt(index));
                attachDragHandlers(li);
                listEl.appendChild(li);
            });
        }

        function attachDragHandlers(li) {
            // Solo se puede iniciar el drag desde el "handle": evita drags accidentales al usar los otros botones.
            const handle = li.querySelector('.mi-handle');
            handle.addEventListener('mousedown', () => { li.draggable = true; });
            li.addEventListener('mouseup', () => { li.draggable = false; });

            li.addEventListener('dragstart', e => {
                dragIndex = Number(li.dataset.index);
                li.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            li.addEventListener('dragend', () => {
                li.classList.remove('dragging');
                li.draggable = false;
                clearDragOverMarks();
                dragIndex = null;
            });
            li.addEventListener('dragover', e => {
                e.preventDefault();
                if (dragIndex === null) return;
                const rect = li.getBoundingClientRect();
                const after = (e.clientY - rect.top) > rect.height / 2;
                li.classList.toggle('drag-over-bottom', after);
                li.classList.toggle('drag-over-top', !after);
            });
            li.addEventListener('dragleave', () => li.classList.remove('drag-over-top', 'drag-over-bottom'));
            li.addEventListener('drop', e => {
                e.preventDefault();
                const dropIndex = Number(li.dataset.index);
                const rect = li.getBoundingClientRect();
                const after = (e.clientY - rect.top) > rect.height / 2;
                clearDragOverMarks();
                if (dragIndex === null) return;
                let target = after ? dropIndex + 1 : dropIndex;
                if (dragIndex < target) target -= 1; // compensar el corrimiento al quitar el elemento origen
                moveTo(dragIndex, target);
                dragIndex = null;
            });
        }

        input.addEventListener('change', () => { if (input.files.length) addFiles(input.files); input.value = ''; });
        addMoreInput.addEventListener('change', () => { if (addMoreInput.files.length) addFiles(addMoreInput.files); addMoreInput.value = ''; });

        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
        ['dragleave', 'dragend'].forEach(evt => dropZone.addEventListener(evt, () => dropZone.classList.remove('drag-over')));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            if (e.dataTransfer?.files?.length) addFiles(e.dataTransfer.files);
        });

        btnRun.addEventListener('click', async () => {
            if (queue.length < 2) return;
            btnRun.disabled = true;
            timer = startProgress(progWrap, progFill, progText, `Uniendo ${queue.length} documentos…`);
            hideAllResults();
            emptyState.style.display = 'none';

            const fd = new FormData();
            fd.append('_token', csrfToken());
            queue.forEach(file => fd.append('pdfs[]', file));

            try {
                const res  = await fetch('{{ route("pdf-tools-pro.merge") }}', { method: 'POST', body: fd });
                const data = await res.json();
                if (!res.ok || data.error) { toast(data.error || 'Error al unir los PDF.', 'error'); return; }

                meta.innerHTML = `
                    <span><i class="fas fa-file-pdf"></i> ${data.documents.length} documentos</span>
                    <span><i class="fas fa-file-lines"></i> ${data.total_pages} páginas totales</span>
                `;
                indexBody.innerHTML = data.documents.map((doc, i) => `
                    <tr>
                        <td class="mit-num">${i + 1}</td>
                        <td>${doc.title}</td>
                        <td class="mit-pages">${doc.pages}</td>
                        <td class="mit-fojas">${doc.start_page}–${doc.end_page}</td>
                    </tr>
                `).join('');
                btnDownload.href = data.download_url;

                hideAllResults();
                resultPanel.classList.add('show');
                resultCardTitle.textContent = 'PDF unido';
                resultCardSub.textContent   = 'Revisá el índice y descargá el documento final';
                toast('¡PDF unido correctamente!', 'success');
            } catch (err) {
                toast('Error de red: ' + err.message, 'error');
            } finally {
                stopProgress(progWrap, progFill, timer);
                btnRun.disabled = queue.length < 2;
            }
        });

        render();
    })();

})();
</script>
@endpush
