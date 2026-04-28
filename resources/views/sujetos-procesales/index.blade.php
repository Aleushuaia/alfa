@extends('layouts.dashboard')

@section('title', 'Extraer Sujetos Procesales — ' . config('app.name', 'Alfa'))
@section('page-title', 'Extraer Sujetos Procesales')
@section('breadcrumb', 'Sujetos Procesales')

@push('styles')
<style>
/* ── LAYOUT ──────────────────────────────────────────────────────────────── */
.sp-outer {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: .85rem;
    align-items: start;
}
@media (max-width: 900px) { .sp-outer { grid-template-columns: 1fr; } }

/* ── COMPACT CARDS (mismo patrón que word-anonymizer) ────────────────────── */
.wc {
    background: var(--card-bg);
    border-radius: 12px;
    box-shadow: var(--card-shadow, 0 2px 14px rgba(0,0,0,.07));
    border: 1px solid var(--card-border);
    color: var(--body-color);
    overflow: hidden;
}
.wc-head {
    display: flex; align-items: center; gap: .55rem;
    padding: .55rem .85rem; border-bottom: 1px solid var(--card-border);
    flex-wrap: wrap; min-height: 44px;
}
.wc-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; flex-shrink: 0; color: #fff;
}
.wc-icon-red   { background: linear-gradient(135deg,#dc2626,#b91c1c); box-shadow: 0 2px 8px rgba(220,38,38,.3); }
.wc-icon-blue  { background: linear-gradient(135deg,#2563eb,#1d4ed8); box-shadow: 0 2px 8px rgba(37,99,235,.3); }
.wc-icon-green { background: linear-gradient(135deg,#059669,#10b981); box-shadow: 0 2px 8px rgba(16,185,129,.3); }
.wc-title { font-size: .83rem; font-weight: 700; color: var(--heading-color); margin: 0; line-height: 1.2; }
.wc-sub   { font-size: .7rem;  color: var(--muted-color); margin: 0; line-height: 1.2; }
.wc-title-stack { display: flex; flex-direction: column; }
.wc-body    { padding: .8rem; }

/* ── DROP ZONE ───────────────────────────────────────────────────────────── */
.drop-zone {
    border: 2px dashed var(--input-border); border-radius: 10px;
    padding: 1.1rem 1rem; text-align: center; cursor: pointer;
    transition: border-color .2s, background .2s; background: var(--input-bg); position: relative;
}
.drop-zone:hover, .drop-zone.drag-over {
    border-color: #dc2626; background: color-mix(in srgb,#dc2626 5%,var(--input-bg));
}
.drop-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.dz-icon  { font-size: 1.5rem; margin-bottom: .25rem; display: block; }
.dz-title { font-size: .78rem; font-weight: 600; color: var(--heading-color); margin-bottom: .1rem; }
.dz-sub   { font-size: .71rem; color: var(--muted-color); }
.fmt-badges { display: flex; flex-wrap: wrap; gap: .2rem; margin-top: .45rem; justify-content: center; }
.fmt-badge  {
    background: var(--badge-light-bg); color: var(--badge-light-color);
    border: 1px solid var(--badge-light-border); border-radius: 4px;
    font-size: .61rem; font-weight: 700; padding: .08rem .32rem; letter-spacing: .03em;
}

/* ── FILE PREVIEW ────────────────────────────────────────────────────────── */
.file-preview {
    display: none; align-items: center; gap: .6rem;
    background: var(--badge-light-bg); border: 1px solid var(--badge-light-border);
    border-radius: 8px; padding: .5rem .7rem; margin-top: .55rem;
}
.file-preview.show { display: flex; }
.fp-icon   { font-size: 1.15rem; flex-shrink: 0; }
.fp-info   { flex: 1; min-width: 0; }
.fp-name   { font-size: .77rem; font-weight: 600; color: var(--heading-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fp-size   { font-size: .69rem; color: var(--muted-color); }
.fp-remove { background: none; border: none; color: #94a3b8; font-size: .95rem; cursor: pointer; padding: 0 .2rem; flex-shrink: 0; transition: color .2s; }
.fp-remove:hover { color: #ef4444; }

/* ── PROGRESS ────────────────────────────────────────────────────────────── */
.prg-wrap { display: none; margin-top: .65rem; }
.prg-wrap.active { display: block; }
.prg-lbl { font-size: .69rem; color: var(--muted-color); margin-bottom: .18rem; }
.prg-bar { height: 4px; border-radius: 99px; background: var(--badge-light-bg); overflow: hidden; }
.prg-fill {
    height: 100%; width: 0%; border-radius: 99px;
    background: linear-gradient(90deg,#dc2626,#b91c1c); transition: width .5s ease;
    animation: pgpulse 1.4s ease-in-out infinite;
}
.prg-fill.done { animation: none; background: linear-gradient(90deg,#059669,#10b981); }
@keyframes pgpulse { 0%,100%{opacity:1} 50%{opacity:.6} }

/* ── BOTÓN PROCESAR ──────────────────────────────────────────────────────── */
.btn-procesar {
    width: 100%; padding: .48rem .8rem; font-size: .82rem; font-weight: 600;
    background: linear-gradient(135deg,#dc2626,#b91c1c); border: none; border-radius: 8px;
    color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: .4rem;
    transition: opacity .2s, transform .15s; box-shadow: 0 3px 12px rgba(220,38,38,.3); margin-top: .65rem;
}
.btn-procesar:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
.btn-procesar:disabled { opacity: .5; cursor: not-allowed; transform: none; }

/* ── LOG BOX ─────────────────────────────────────────────────────────────── */
.sp-log {
    display: none;
    background: var(--input-bg);
    border: 1px solid var(--input-border);
    border-radius: 8px;
    padding: .5rem .65rem;
    margin-top: .65rem;
    max-height: 120px;
    overflow-y: auto;
    font-family: 'Courier New', Courier, monospace;
    font-size: .7rem;
    line-height: 1.7;
    color: var(--muted-color);
}
.sp-log.active { display: block; }
.sp-log-line { display: flex; align-items: flex-start; gap: .3rem; }
.sp-log-line.ok  .sp-log-dot { color: #10b981; }
.sp-log-line.run .sp-log-dot { color: #f59e0b; }
.sp-log-line.err .sp-log-dot { color: #ef4444; }
.sp-log-dot { flex-shrink: 0; }

/* ── RESULTADO ───────────────────────────────────────────────────────────── */
.result-textarea {
    width: 100%;
    min-height: calc(100vh - 200px);
    font-family: 'Courier New', Courier, monospace;
    font-size: .82rem;
    background: var(--input-bg);
    color: var(--body-color);
    border: 1.5px solid var(--input-border);
    border-radius: 8px;
    padding: .9rem;
    resize: vertical;
    line-height: 1.65;
}
</style>
@endpush

@section('content')
<div class="sp-outer">

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- COLUMNA IZQUIERDA                                                    --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    <div style="display:flex;flex-direction:column;gap:.85rem;">

        {{-- ── Card: Subir PDF ──────────────────────────────────────────── --}}
        <div class="wc">
            <div class="wc-head">
                <div class="wc-icon wc-icon-red"><i class="fas fa-file-pdf"></i></div>
                <div class="wc-title-stack">
                    <span class="wc-title">Subir documento PDF</span>
                    <span class="wc-sub">PDF · máx. 50 MB</span>
                </div>
            </div>
            <div class="wc-body">
                <div class="drop-zone" id="dropZone">
                    <input type="file" id="pdfInput" accept=".pdf,application/pdf">
                    <i class="fas fa-file-pdf dz-icon" style="color:#dc2626;"></i>
                    <div class="dz-title">Arrastrá o hacé clic</div>
                    <div class="dz-sub">para seleccionar PDF</div>
                    <div class="fmt-badges">
                        <span class="fmt-badge" style="background:color-mix(in srgb,#dc2626 12%,var(--badge-light-bg));border-color:color-mix(in srgb,#dc2626 25%,transparent);color:#dc2626;">PDF</span>
                    </div>
                </div>
                <div class="file-preview" id="filePreview">
                    <i class="fp-icon fas fa-file-pdf" style="color:#dc2626;"></i>
                    <div class="fp-info">
                        <div class="fp-name" id="fpName"></div>
                        <div class="fp-size" id="fpSize"></div>
                    </div>
                    <button type="button" class="fp-remove" id="btnRemove" title="Quitar archivo">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Card: Prompt + Acción ────────────────────────────────────── --}}
        <div class="wc">
            <div class="wc-head">
                <div class="wc-icon wc-icon-blue"><i class="fas fa-file-code"></i></div>
                <div class="wc-title-stack">
                    <span class="wc-title">Prompt y extracción</span>
                    <span class="wc-sub">Seleccioná el prompt a usar</span>
                </div>
            </div>
            <div class="wc-body">

                <label class="form-label fw-semibold"
                       style="font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;color:var(--muted-color);margin-bottom:.3rem;display:block;">
                    Prompt
                </label>
                <select id="promptSelect" class="form-select form-select-sm">
                    <option value="">Seleccionar prompt…</option>
                    @foreach($prompts as $p)
                        <option value="{{ $p->id }}">{{ $p->descripcion }}</option>
                    @endforeach
                </select>
                @if($prompts->isEmpty())
                    <div style="font-size:.71rem;color:var(--muted-color);margin-top:.3rem;">
                        <i class="fas fa-info-circle me-1"></i>No hay prompts.
                        @if(auth()->user()->hasRole('administrador'))
                            <a href="{{ route('admin.prompts.index') }}" style="color:#dc2626;">Creá uno aquí.</a>
                        @endif
                    </div>
                @endif

                <button type="button" id="btnExtraer" class="btn-procesar" disabled>
                    <i class="fas fa-magnifying-glass"></i>
                    Extraer sujetos procesales
                </button>

                {{-- Barra de progreso --}}
                <div class="prg-wrap" id="prgWrap">
                    <div class="prg-lbl" id="prgLbl">Iniciando…</div>
                    <div class="prg-bar"><div class="prg-fill" id="prgFill"></div></div>
                </div>

                {{-- Log de etapas --}}
                <div class="sp-log" id="spLog"></div>

                {{-- Mensaje de error --}}
                <div id="spError"
                     style="display:none;margin-top:.6rem;font-size:.77rem;color:#dc2626;
                            background:color-mix(in srgb,#dc2626 8%,var(--card-bg));
                            border:1px solid color-mix(in srgb,#dc2626 22%,transparent);
                            border-radius:8px;padding:.4rem .65rem;">
                    <i class="fas fa-circle-exclamation me-1"></i><span id="spErrorMsg"></span>
                </div>

            </div>
        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- COLUMNA DERECHA: resultado                                           --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    <div class="wc">
        <div class="wc-head">
            <div class="wc-icon wc-icon-green"><i class="fas fa-users"></i></div>
            <div class="wc-title-stack">
                <span class="wc-title">Sujetos procesales detectados</span>
                <span class="wc-sub">Resultado en formato JSON</span>
            </div>
        </div>
        <div class="wc-body">
            <textarea id="resultArea"
                      class="result-textarea"
                      readonly
                      placeholder="El resultado aparecerá aquí en formato JSON una vez completado el proceso…"></textarea>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    /* ── Elementos ─────────────────────────────────────────────────────── */
    const dropZone   = document.getElementById('dropZone');
    const pdfInput   = document.getElementById('pdfInput');
    const filePreview= document.getElementById('filePreview');
    const fpName     = document.getElementById('fpName');
    const fpSize     = document.getElementById('fpSize');
    const btnRemove  = document.getElementById('btnRemove');
    const promptSel  = document.getElementById('promptSelect');
    const btnExtraer = document.getElementById('btnExtraer');
    const prgWrap    = document.getElementById('prgWrap');
    const prgLbl     = document.getElementById('prgLbl');
    const prgFill    = document.getElementById('prgFill');
    const spLog      = document.getElementById('spLog');
    const spError    = document.getElementById('spError');
    const spErrorMsg = document.getElementById('spErrorMsg');
    const resultArea = document.getElementById('resultArea');

    /* ── Pasos del pipeline (simulados del lado cliente) ────────────────── */
    const STEPS = [
        { pct: 10, label: 'Iniciando proceso…',                delay: 0     },
        { pct: 25, label: 'Extrayendo texto del PDF vía OCR…', delay: 900   },
        { pct: 45, label: 'Procesando páginas del documento…', delay: 3500  },
        { pct: 62, label: 'Extracción de texto finalizada.',   delay: 7000  },
        { pct: 72, label: 'Construyendo prompt con el texto…', delay: 8000  },
        { pct: 82, label: 'Enviando prompt al modelo LLM…',   delay: 9500  },
        { pct: 91, label: 'Esperando respuesta del modelo…',  delay: 13000 },
    ];

    let stepTimers = [];
    let hasFile    = false;

    /* ── Drag & Drop ────────────────────────────────────────────────────── */
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        if (e.dataTransfer.files.length) setFile(e.dataTransfer.files[0]);
    });
    pdfInput.addEventListener('change', () => { if (pdfInput.files.length) setFile(pdfInput.files[0]); });
    btnRemove.addEventListener('click', clearFile);

    function setFile(file) {
        hasFile = true;
        fpName.textContent = file.name;
        fpSize.textContent = formatBytes(file.size);
        filePreview.classList.add('show');
        checkBtn();
    }

    function clearFile() {
        hasFile = false;
        pdfInput.value = '';
        filePreview.classList.remove('show');
        checkBtn();
    }

    function formatBytes(b) {
        if (b < 1024)    return b + ' B';
        if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
        return (b / 1048576).toFixed(2) + ' MB';
    }

    /* ── Habilitar botón ────────────────────────────────────────────────── */
    promptSel.addEventListener('change', checkBtn);
    function checkBtn() { btnExtraer.disabled = !(hasFile && promptSel.value); }

    /* ── Log helpers ────────────────────────────────────────────────────── */
    function logLine(text, type = 'run') {
        const icons = { run: '▶', ok: '✔', err: '✖' };
        const d = document.createElement('div');
        d.className = `sp-log-line ${type}`;
        d.innerHTML = `<span class="sp-log-dot">${icons[type]}</span><span>${text}</span>`;
        spLog.appendChild(d);
        spLog.scrollTop = spLog.scrollHeight;
        return d;
    }

    function markLastRunAsOk() {
        const runs = spLog.querySelectorAll('.sp-log-line.run');
        if (!runs.length) return;
        const last = runs[runs.length - 1];
        last.classList.replace('run', 'ok');
        last.querySelector('.sp-log-dot').textContent = '✔';
    }

    function setProgress(pct, label, done = false) {
        prgFill.style.width = pct + '%';
        prgLbl.textContent  = label;
        if (done) prgFill.classList.add('done');
        else      prgFill.classList.remove('done');
    }

    function clearTimers() { stepTimers.forEach(clearTimeout); stepTimers = []; }

    /* ── Extracción ─────────────────────────────────────────────────────── */
    btnExtraer.addEventListener('click', async () => {
        hideError();
        resultArea.value = '';
        spLog.innerHTML  = '';
        clearTimers();

        if (!pdfInput.files.length) { showError('Debe seleccionar un archivo PDF.'); return; }
        if (!promptSel.value)       { showError('Debe seleccionar un prompt.');      return; }

        /* Mostrar log y progreso */
        spLog.classList.add('active');
        prgWrap.classList.add('active');
        btnExtraer.disabled = true;

        /* Disparar pasos simulados */
        STEPS.forEach(({ pct, label, delay }, idx) => {
            const t = setTimeout(() => {
                if (idx > 0) markLastRunAsOk();
                setProgress(pct, label);
                logLine(label, 'run');
            }, delay);
            stepTimers.push(t);
        });

        const formData = new FormData();
        formData.append('pdf',       pdfInput.files[0]);
        formData.append('prompt_id', promptSel.value);
        formData.append('_token',    getCsrf());

        try {
            const response = await fetch('{{ route("sujetos-procesales.extraer") }}', {
                method: 'POST',
                body:   formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            clearTimers();
            const data = await response.json();

            if (!response.ok) {
                markLastRunAsOk();
                logLine('Error: ' + (data.error ?? 'Respuesta no válida del servidor.'), 'err');
                setProgress(0, 'Error en el proceso.');
                prgFill.classList.remove('done');
                showError(data.error ?? 'Ocurrió un error en el servidor.');
                return;
            }

            /* ── Éxito ── */
            markLastRunAsOk();
            logLine('Parseando respuesta del modelo…', 'ok');
            logLine('¡Proceso finalizado con éxito!', 'ok');
            setProgress(100, '¡Extracción completada!', true);

            if (data.resultado !== undefined) {
                resultArea.value = typeof data.resultado === 'object'
                    ? JSON.stringify(data.resultado, null, 2)
                    : data.resultado;
            } else {
                resultArea.value = JSON.stringify(data, null, 2);
            }

        } catch (err) {
            clearTimers();
            logLine('Error de red: ' + err.message, 'err');
            setProgress(0, 'Error en el proceso.');
            showError('Error de red o respuesta inesperada.');
            console.error(err);
        } finally {
            btnExtraer.disabled = !(hasFile && promptSel.value);
        }
    });

    /* ── Helpers ────────────────────────────────────────────────────────── */
    function showError(msg) {
        spErrorMsg.textContent = msg;
        spError.style.display  = 'block';
    }
    function hideError() {
        spErrorMsg.textContent = '';
        spError.style.display  = 'none';
    }
    function getCsrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }
})();
</script>
@endpush
