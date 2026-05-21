@extends('layouts.dashboard')

@section('title', 'Anonimizador de Word  ' . config('app.name', 'Alfa'))
@section('page-title', 'Anonimizador de Word')
@section('breadcrumb', 'Word')

@push('styles')
<style>
/*  LAYOUT  */
.wa-outer {
    display: grid;
    grid-template-columns: 244px 1fr;
    gap: .85rem;
    align-items: start;
    transition: grid-template-columns .3s ease;
}
.wa-outer.collapsed { grid-template-columns: 72px 1fr; }
.wa-sidebar { display: flex; flex-direction: column; gap: .85rem; transition: width .3s ease; width: 244px; }
.wa-sidebar.collapsed { width: 72px; overflow: hidden; }
.wa-sidebar.collapsed .wc-head { padding: .5rem; }
.wa-sidebar.collapsed .wc-body, .wa-sidebar.collapsed .wc-body-sm { padding: .5rem; }
.wa-sidebar.collapsed .wc-title, .wa-sidebar.collapsed .wc-sub, .wa-sidebar.collapsed .et-lbl, .wa-sidebar.collapsed .dz-title, .wa-sidebar.collapsed .dz-sub, .wa-sidebar.collapsed .fmt-badges { display: none; }\n.wa-sidebar.collapsed .collapse-label { display: none; }
.wa-main    { display: grid; grid-template-columns: 1fr; gap: .85rem; min-width: 0; }
.wa-main.split { grid-template-columns: 1.25fr 0.75fr; }
.wa-outer.collapsed .wa-main.split { grid-template-columns: 1.2fr 0.8fr; }
@media (max-width: 1100px) { .wa-outer { grid-template-columns: 210px 1fr; } .wa-outer.collapsed { grid-template-columns: 72px 1fr; } }
@media (max-width: 820px)  { .wa-outer { grid-template-columns: 1fr; } .wa-main.split { grid-template-columns: 1fr; } .wa-outer.collapsed { grid-template-columns: 1fr; } }

/*  COMPACT CARDS  */
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
.wc-head.between { justify-content: space-between; }
.wc-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; flex-shrink: 0; color: #fff;
}
.wc-icon-blue   { background: linear-gradient(135deg,#2563eb,#1d4ed8); box-shadow: 0 2px 8px rgba(37,99,235,.3); }
.wc-icon-green  { background: linear-gradient(135deg,#059669,#10b981); box-shadow: 0 2px 8px rgba(16,185,129,.3); }
.wc-icon-purple { background: linear-gradient(135deg,#7c3aed,#6d28d9); box-shadow: 0 2px 8px rgba(124,58,237,.3); }
.wc-title { font-size: .83rem; font-weight: 700; color: var(--heading-color); margin: 0; line-height: 1.2; }
.wc-sub   { font-size: .7rem;  color: var(--muted-color); margin: 0; line-height: 1.2; }
.wc-title-stack { display: flex; flex-direction: column; }
.wc-body    { padding: .8rem; }
.wc-body-sm { padding: .55rem .8rem; }
.wc-foot    { padding: .45rem .8rem; border-top: 1px solid var(--card-border); font-size: .71rem; color: var(--muted-color); }

/*  DROP ZONE  */
.drop-zone {
    border: 2px dashed var(--input-border); border-radius: 10px;
    padding: 1.1rem 1rem; text-align: center; cursor: pointer;
    transition: border-color .2s, background .2s; background: var(--input-bg); position: relative;
}
.drop-zone:hover, .drop-zone.drag-over {
    border-color: #2563eb; background: color-mix(in srgb,#2563eb 5%,var(--input-bg));
}
.drop-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.dz-icon  { font-size: 1.5rem; color: #2563eb; margin-bottom: .25rem; display: block; }
.dz-title { font-size: .78rem; font-weight: 600; color: var(--heading-color); margin-bottom: .1rem; }
.dz-sub   { font-size: .71rem; color: var(--muted-color); }
.fmt-badges { display: flex; flex-wrap: wrap; gap: .2rem; margin-top: .45rem; justify-content: center; }
.fmt-badge  {
    background: var(--badge-light-bg); color: var(--badge-light-color);
    border: 1px solid var(--badge-light-border); border-radius: 4px;
    font-size: .61rem; font-weight: 700; padding: .08rem .32rem; letter-spacing: .03em;
}

/*  File preview  */
.file-preview {
    display: none; align-items: center; gap: .6rem;
    background: var(--badge-light-bg); border: 1px solid var(--badge-light-border);
    border-radius: 8px; padding: .5rem .7rem; margin-top: .55rem;
}
.file-preview.show { display: flex; }
.fp-icon   { font-size: 1.15rem; color: #2563eb; flex-shrink: 0; }
.fp-info   { flex: 1; min-width: 0; }
.fp-name   { font-size: .77rem; font-weight: 600; color: var(--heading-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fp-size   { font-size: .69rem; color: var(--muted-color); }
.fp-remove { background: none; border: none; color: #94a3b8; font-size: .95rem; cursor: pointer; padding: 0 .2rem; flex-shrink: 0; transition: color .2s; }
.fp-remove:hover { color: #ef4444; }

/*  BUTTONS  */
.btn-hdr {
    display: inline-flex; align-items: center; gap: .28rem;
    padding: .26rem .58rem; border-radius: 6px; font-size: .73rem; font-weight: 600;
    border: none; cursor: pointer; transition: opacity .18s, transform .12s; white-space: nowrap;
}
.btn-hdr:hover:not(:disabled) { opacity: .87; transform: translateY(-1px); }
.btn-hdr:disabled { opacity: .45; cursor: not-allowed; }
.btn-hdr-blue   { background: linear-gradient(135deg,#2563eb,#1d4ed8); color:#fff; box-shadow: 0 2px 8px rgba(37,99,235,.28); }
.btn-hdr-green  { background: linear-gradient(135deg,#059669,#10b981); color:#fff; box-shadow: 0 2px 8px rgba(16,185,129,.28); }
.btn-hdr-amber  { background: linear-gradient(135deg,#d97706,#b45309); color:#fff; box-shadow: 0 2px 8px rgba(217,119,6,.28); }
.btn-hdr-muted  { background: var(--badge-light-bg); color: var(--body-color); border: 1px solid var(--badge-light-border); }
.btn-hdr-danger { background: var(--alert-danger-bg,#fee2e2); color: var(--alert-danger-color,#dc2626); }
.btn-procesar {
    width: 100%; padding: .48rem .8rem; font-size: .82rem; font-weight: 600;
    background: linear-gradient(135deg,#2563eb,#1d4ed8); border: none; border-radius: 8px;
    color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: .4rem;
    transition: opacity .2s, transform .15s; box-shadow: 0 3px 12px rgba(37,99,235,.3); margin-top: .6rem;
}
.btn-procesar:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
.btn-procesar:disabled { opacity: .5; cursor: not-allowed; }
.btn-dl-word {
    width: 100%; padding: .48rem .8rem; font-size: .82rem; font-weight: 600;
    background: linear-gradient(135deg,#059669,#10b981); border: none; border-radius: 8px;
    color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: .4rem;
    transition: opacity .2s, transform .15s; box-shadow: 0 3px 12px rgba(16,185,129,.3); text-decoration: none;
}
.btn-dl-word:hover { opacity: .9; transform: translateY(-1px); color: #fff; }

/*  COLLAPSE BUTTON  */
.btn-collapse-main {
    background: var(--card-bg); border: 1px solid var(--card-border); color: var(--body-color); cursor: pointer;
    padding: .4rem .6rem; font-size: .82rem; font-weight: 600; white-space: nowrap;
    display: inline-flex; align-items: center; gap: .4rem; transition: all .14s ease;
    border-radius: 8px; flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,.06); z-index: 30;
}
.btn-collapse-main:hover {
    background: color-mix(in srgb, var(--card-bg) 85%, #000 15%);
    transform: translateX(-2px);
}
.collapse-label { display: inline; }

/*  PROGRESS  */
.prg-wrap { display: none; }
.prg-wrap.active { display: block; }
.prg-lbl { font-size: .69rem; color: var(--muted-color); margin-bottom: .18rem; }
.prg-bar { height: 4px; border-radius: 99px; background: var(--badge-light-bg); overflow: hidden; }
.prg-fill {
    height: 100%; width: 0%; border-radius: 99px;
    background: linear-gradient(90deg,#2563eb,#1d4ed8); transition: width .4s;
    animation: pgpulse 1.4s ease-in-out infinite;
}
.prg-fill-green { background: linear-gradient(90deg,#059669,#10b981); }
@keyframes pgpulse { 0%,100%{opacity:1} 50%{opacity:.6} }

/*  ENTITY TYPE SWITCHES  */
.et-row   { display: flex; align-items: center; gap: .42rem; padding: .16rem 0; }
.et-sw    { position: relative; width: 30px; height: 16px; flex-shrink: 0; }
.et-sw input { opacity: 0; width: 0; height: 0; }
.et-track {
    position: absolute; inset: 0; background: #ccc; border-radius: 16px;
    cursor: pointer; transition: background .2s;
}
.et-track::before {
    content: ''; position: absolute;
    width: 12px; height: 12px; left: 2px; bottom: 2px;
    background: #fff; border-radius: 50%; transition: transform .2s;
}
.et-sw input:checked + .et-track { background: #2563eb; }
.et-sw input:checked + .et-track::before { transform: translateX(14px); }
.et-lbl { font-size: .77rem; cursor: pointer; user-select: none; }
.et-lbl.disabled { opacity: .4; text-decoration: line-through; }
.leg-dot { display: inline-block; width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }

/*  EDITOR  */
.wa-editor {
    border: 1.5px solid var(--input-border); border-radius: 8px;
    overflow-y: auto; padding: .7rem .9rem;
    background: var(--input-bg); line-height: 1.8; font-size: .875rem;
    color: var(--body-color); overflow-wrap: break-word; word-break: break-word;
    min-height: 120px; max-height: calc(100vh - 160px);
    flex: 1;
}
.wa-editor[data-empty="true"]::before {
    content: attr(data-placeholder); color: var(--input-placeholder,#b0bec5);
    font-style: italic; pointer-events: none; display: block;
}
.wa-empty {
    text-align: center; padding: 1.5rem 1rem 1rem; color: var(--muted-color);
    border: 1.5px dashed var(--input-border); border-radius: 8px; margin-bottom: .5rem;
    background: var(--input-bg);
}
.wa-empty i  { font-size: 1.5rem; display: block; margin-bottom: .4rem; }
.wa-empty p  { font-size: .79rem; line-height: 1.65; margin: 0; }

/*  ENTITY SPANS  */
.entity {
    padding: 1px 4px; border-radius: 4px; font-weight: 500;
    cursor: pointer; position: relative; display: inline;
}
.entity.person   { background: {{ $entityColors["PER"]   ?? "#ffcccc" }}; }
.entity.org      { background: {{ $entityColors["ORG"]   ?? "#cce5ff" }}; }
.entity.location { background: {{ $entityColors["LOC"]   ?? "#ccffcc" }}; }
.entity.date     { background: {{ $entityColors["DATE"]  ?? "#ffe0b3" }}; }
.entity.dni      { background: {{ $entityColors["DNI"]   ?? "#e0e0e0" }}; }
.entity.email    { background: {{ $entityColors["EMAIL"] ?? "#ccf2ff" }}; }
.entity.phone    { background: {{ $entityColors["PHONE"] ?? "#ffffcc" }}; }
.entity.misc     { background: {{ $entityColors["MISC"]  ?? "#e0ccff" }}; }
.entity-flash    { background: #ccff00 !important; color: #000 !important; box-shadow: 0 0 16px rgba(204,255,0,.9); border-radius: 4px; }
.entity-hover    { background: #ccff00 !important; color: #000 !important; box-shadow: 0 0 10px rgba(204,255,0,.8); border-radius: 4px; }

/*  TOOLTIP  */
#wa-tip {
    display: none; position: fixed; z-index: 99999;
    background: rgba(15,23,42,.88); color: #f8fafc;
    font-size: .7rem; font-weight: 600; padding: .22rem .55rem; border-radius: 6px;
    pointer-events: none; white-space: nowrap; letter-spacing: .025em;
    box-shadow: 0 4px 16px rgba(0,0,0,.25);
}
#wa-tip::after {
    content: ''; position: absolute; top: 100%; left: 50%; transform: translateX(-50%);
    border: 4px solid transparent; border-top-color: rgba(15,23,42,.88);
}

/*  CONTEXT MENU  */
#wa-ctx {
    display: none; position: fixed; z-index: 99998;
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: 8px; box-shadow: 0 6px 24px rgba(0,0,0,.18);
    padding: 4px 0; min-width: 200px;
}
#wa-ctx button {
    display: block; width: 100%; padding: 7px 13px;
    background: none; border: none; text-align: left; font-size: .8rem;
    cursor: pointer; white-space: nowrap; color: var(--body-color);
}
#wa-ctx button:hover { background: var(--table-hover-bg,#f0f4ff); }
#wa-ctx hr { margin: 3px 0; border-color: var(--card-border); }
#wa-ctx .ctx-head { padding: 4px 13px 2px; font-size: .68rem; color: var(--muted-color); font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
#wa-ctx .ctx-preview { padding: 2px 13px 4px; font-size: .76rem; font-weight: 600; color: var(--heading-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 230px; }
#wa-ctx button.loading { opacity: .6; pointer-events: none; }
#wa-ctx .ctx-type-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3px; padding: 4px 8px 6px; }
#wa-ctx .ctx-type-opt { display: flex; align-items: center; gap: 5px; padding: 5px 7px; border: 1px solid var(--card-border); border-radius: 5px; background: none; font-size: .75rem; cursor: pointer; text-align: left; color: var(--body-color); width: 100%; transition: background .12s; }
#wa-ctx .ctx-type-opt:hover { background: var(--table-hover-bg,#f0f4ff); }
#wa-ctx .ctx-type-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; border: 1px solid rgba(0,0,0,.12); display: inline-block; }
.wa-editor[contenteditable="false"] { cursor: default; user-select: text; }

/*  ENTITY TABLE  */
.ent-scroll { overflow-y: auto; max-height: calc(100vh - 220px); -webkit-overflow-scrolling: touch; }
.entity-row.jumping td:first-child { background: color-mix(in srgb,#2563eb 12%,var(--table-hover-bg,#f0f4ff)); }

/*  DOWNLOAD CARD  */
#download-card { border-color: #10b981; background: var(--card-bg); }
#download-card .wc-head { border-bottom: 1px solid var(--card-border); }
#download-card .wc-icon-green { background: linear-gradient(135deg,#059669,#10b981); box-shadow: 0 2px 8px rgba(16,185,129,.3); }
#download-card .wc-title { color: var(--heading-color); }
#download-card .btn-dl-word { background: linear-gradient(135deg,#047857,#059669); border: none; color: #fff; font-weight: 600; padding: .5rem .9rem; border-radius: 8px; box-shadow: 0 3px 12px rgba(16,185,129,.3); }
#download-card .btn-dl-word:hover { background: linear-gradient(135deg,#065f46,#047857); color: #fff; transform: translateY(-1px); }

/*  TOASTS  */
#wa-toasts { position: fixed; bottom: 1.3rem; right: 1.3rem; z-index: 100000; display: flex; flex-direction: column; gap: .4rem; }
.wa-toast {
    display: flex; align-items: center; gap: .45rem; background: var(--card-bg);
    padding: .55rem .85rem; border-radius: 10px; box-shadow: 0 4px 18px rgba(0,0,0,.15);
    font-size: .78rem; font-weight: 500; color: var(--body-color);
    border-left: 4px solid #2563eb; animation: waIn .25s ease; min-width: 210px; max-width: 330px;
}
.wa-toast.ts  { border-left-color: #22c55e; } .wa-toast.ts i { color: #22c55e; }
.wa-toast.te  { border-left-color: #ef4444; } .wa-toast.te i { color: #ef4444; }
.wa-toast.ti  { border-left-color: #2563eb; } .wa-toast.ti i { color: #2563eb; }
.wa-toast i   { font-size: .88rem; flex-shrink: 0; }
@keyframes waIn { from{opacity:0;transform:translateX(18px)} to{opacity:1;transform:translateX(0)} }
</style>
@endpush

@section('content')
<div style="display: flex; align-items: center; gap: .5rem; margin-bottom: .85rem;">
    <span style="font-size: .78rem; color: var(--muted-color);">Procesamiento de Texto</span>
</div>
<div class="wa-outer" id="wa-outer">

    {{--  SIDEBAR  --}}
    <div class="wa-sidebar" id="wa-sidebar">

        {{-- Upload WORD --}}
        <div class="wc">
            <div class="wc-head">
                <div class="wc-icon wc-icon-blue"><i class="fas fa-file-word"></i></div>
                <div class="wc-title-stack">
                    <span class="wc-title">Subir documento Word</span>
                    <span class="wc-sub">Solo .doc y .docx · máx. 50 MB</span>
                </div>
            </div>
            <div class="wc-body">
                <form id="wa-form" novalidate>
                    @csrf
                    <div class="drop-zone" id="drop-zone">
                        <input type="file" id="word-input" name="word"
                               accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                        <i class="fas fa-file-word dz-icon"></i>
                        <div class="dz-title">Arrastrá o hacé clic</div>
                        <div class="dz-sub">para seleccionar archivo Word</div>
                        <div class="fmt-badges">
                            <span class="fmt-badge">DOCX</span><span class="fmt-badge">DOC</span>
                        </div>
                    </div>
                    <div class="file-preview" id="file-preview">
                        <i class="fp-icon fas fa-file-word"></i>
                        <div class="fp-info">
                            <div class="fp-name" id="fp-name"></div>
                            <div class="fp-size" id="fp-size"></div>
                        </div>
                        <button type="button" class="fp-remove" id="btn-remove" title="Quitar archivo">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                    <div class="prg-wrap mt-2" id="prg-up">
                        <div class="prg-lbl">Extrayendo texto</div>
                        <div class="prg-bar"><div class="prg-fill" id="prg-up-fill"></div></div>
                    </div>
                    <button type="submit" class="btn-procesar" id="btn-procesar" disabled>
                        <i class="fas fa-file-export"></i> Procesar Word
                    </button>
                </form>
            </div>
        </div>

        {{-- Download WORD (hidden, shown only when source is Word) --}}
        <div class="wc" id="download-card" style="display:none;">
            <div class="wc-head">
                <div class="wc-icon wc-icon-green"><i class="fas fa-file-word"></i></div>
                <div class="wc-title-stack">
                    <span class="wc-title">Listo para descargar</span>
                    <span class="wc-sub">Documento Word anonimizado</span>
                </div>
            </div>
            <div class="wc-body">
                <p style="font-size:.77rem;color:var(--muted-color);margin-bottom:.65rem;">
                    <i class="fas fa-circle-check me-1" style="color:#10b981;"></i>
                    Anonimización completada correctamente.
                </p>
                <a href="{{ route('word-anonymizer.download') }}" class="btn-dl-word" target="_blank">
                    <i class="fas fa-file-arrow-down"></i> Descargar .docx
                </a>
            </div>
        </div>

        {{-- Upload PDF --}}
        <div class="wc">
            <div class="wc-head">
                <div class="wc-icon" style="background:linear-gradient(135deg,#dc2626,#b91c1c);box-shadow:0 2px 8px rgba(220,38,38,.3);">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <div class="wc-title-stack">
                    <span class="wc-title">Subir documento PDF</span>
                    <span class="wc-sub">PDF · máx. 50 MB</span>
                </div>
            </div>
            <div class="wc-body">
                <form id="wa-pdf-form" novalidate>
                    @csrf
                    <div class="drop-zone" id="pdf-drop-zone">
                        <input type="file" id="pdf-input" name="pdf"
                               accept=".pdf,application/pdf">
                        <i class="fas fa-file-pdf dz-icon" style="color:#dc2626;"></i>
                        <div class="dz-title">Arrastrá o hacé clic</div>
                        <div class="dz-sub">para seleccionar PDF</div>
                        <div class="fmt-badges">
                            <span class="fmt-badge" style="background:color-mix(in srgb,#dc2626 12%,var(--badge-light-bg));border-color:color-mix(in srgb,#dc2626 25%,transparent);color:#dc2626;">PDF</span>
                        </div>
                    </div>
                    <div class="file-preview" id="pdf-file-preview">
                        <i class="fp-icon fas fa-file-pdf" style="color:#dc2626;"></i>
                        <div class="fp-info">
                            <div class="fp-name" id="pdf-fp-name"></div>
                            <div class="fp-size" id="pdf-fp-size"></div>
                        </div>
                        <button type="button" class="fp-remove" id="btn-pdf-remove" title="Quitar archivo">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                    <div class="prg-wrap mt-2" id="prg-pdf-up">
                        <div class="prg-lbl">Extrayendo texto del PDF</div>
                        <div class="prg-bar"><div class="prg-fill" id="prg-pdf-up-fill" style="background:linear-gradient(90deg,#dc2626,#b91c1c);"></div></div>
                    </div>
                    <button type="submit" class="btn-procesar" id="btn-procesar-pdf" disabled
                            style="background:linear-gradient(135deg,#dc2626,#b91c1c);box-shadow:0 3px 12px rgba(220,38,38,.3);">
                        <i class="fas fa-file-export"></i> Procesar PDF
                    </button>
                </form>
            </div>
        </div>

        {{-- Entity types --}}
        <div class="wc">
            <div class="wc-head">
                <div class="wc-icon wc-icon-purple"><i class="fas fa-palette"></i></div>
                <div class="wc-title-stack"><span class="wc-title">Tipos de entidades</span></div>
            </div>
            <div class="wc-body-sm">
                @php
                    $etList = [
                        ['type'=>'PER','label'=>'Persona'],['type'=>'ORG','label'=>'Organización'],
                        ['type'=>'LOC','label'=>'Lugar'],  ['type'=>'DATE','label'=>'Fecha'],
                        ['type'=>'DNI','label'=>'DNI'],    ['type'=>'EMAIL','label'=>'Email'],
                        ['type'=>'PHONE','label'=>'Teléfono'],['type'=>'MISC','label'=>'Otros'],
                    ];
                @endphp
                @foreach($etList as $et)
                <div class="et-row">
                    <label class="et-sw" title="{{ $et['label'] }}">
                        <input type="checkbox" class="et-cb" data-entity-type="{{ $et['type'] }}" checked>
                        <span class="et-track"></span>
                    </label>
                    <span class="leg-dot" style="background:{{ $entityColors[$et['type']] ?? '#ddd' }}"></span>
                    <span class="et-lbl" data-entity-type="{{ $et['type'] }}">{{ $et['label'] }}</span>
                </div>
                @endforeach
                <div class="d-flex gap-2 mt-2" style="font-size:.7rem;">
                    <a href="#" id="btn-all" class="text-decoration-none">Todas</a>
                    <span class="text-muted">|</span>
                    <a href="#" id="btn-none" class="text-decoration-none">Ninguna</a>
                </div>
            </div>
        </div>

        {{-- Completion card for PDF / plain (no Word download) --}}
        <div class="wc" id="done-card" style="display:none;border-color:#10b981;">
            <div class="wc-head">
                <div class="wc-icon wc-icon-green"><i class="fas fa-circle-check"></i></div>
                <div class="wc-title-stack">
                    <span class="wc-title">Anonimización completada</span>
                    <span class="wc-sub" id="done-card-sub">Texto anonimizado</span>
                </div>
            </div>
            <div class="wc-body">
                <p style="font-size:.77rem;color:var(--muted-color);margin-bottom:.65rem;">
                    El texto en el panel central refleja los cambios. Podés copiarlo o descargarlo como .txt.
                </p>
                <button class="btn-dl-word" id="btn-dl-txt-done" style="background:linear-gradient(135deg,#059669,#10b981);">
                    <i class="fas fa-file-arrow-down"></i> Descargar .txt
                </button>
            </div>
        </div>

    </div>{{-- /sidebar --}}

    {{--  MAIN  --}}
    <div class="wa-main" id="wa-main">

        {{-- TEXT PANEL --}}
        <div class="wc" id="wa-text-card" style="display:flex;flex-direction:column;">
            <div class="wc-head between">
                <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0;min-width:0;">
                    <div class="wc-icon wc-icon-green"><i class="fas fa-file-lines"></i></div>
                    <div class="wc-title-stack">
                        <span class="wc-title">Texto a analizar
                            <span id="source-badge" style="display:none;margin-left:.35rem;font-size:.63rem;font-weight:600;padding:.1rem .38rem;border-radius:10px;vertical-align:middle;background:var(--badge-light-bg);color:var(--muted-color);border:1px solid var(--badge-light-border);"></span>
                        </span>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:.3rem;flex-wrap:wrap;flex-shrink:0;">
                    <button class="btn-hdr btn-hdr-blue"   id="btn-analizar"  disabled><i class="fas fa-search"></i> Buscar Entidades</button>
                    <button class="btn-hdr btn-hdr-muted"  id="btn-copy"      disabled><i class="fas fa-copy"></i> Copiar</button>
                    <button class="btn-hdr btn-hdr-muted"  id="btn-dl-txt"    disabled><i class="fas fa-file-arrow-down"></i> .txt</button>
                    <button class="btn-hdr btn-hdr-danger" id="btn-clear"     disabled><i class="fas fa-trash-alt"></i> Limpiar</button>
                </div>
            </div>
            <div class="prg-wrap px-3" id="prg-an" style="padding:.3rem .8rem 0;">
                <div class="prg-lbl">Analizando con NLP</div>
                <div class="prg-bar"><div class="prg-fill" id="prg-an-fill"></div></div>
            </div>
            <div style="padding:.45rem .75rem .6rem;flex:1;display:flex;flex-direction:column;">
                <div class="wa-empty" id="wa-empty">
                    <i class="fas fa-keyboard"></i>
                    <p>
                        <strong>Tres formas de cargar texto:</strong><br>
                        <span style="font-size:.77rem;">
                            <i class="fas fa-file-word me-1" style="color:#2563eb;"></i>Procesá un <strong>Word</strong> o un <strong>PDF</strong> con los paneles de la izquierda,<br>
                            o <i class="fas fa-paste me-1" style="color:#059669;"></i><strong>pegá texto directamente</strong> en el área de abajo.
                        </span>
                    </p>
                </div>
                <div id="wa-editor"
                     contenteditable="true"
                     data-placeholder="Pegá o escribí el texto aquí para analizarlo y anonimizarlo…"
                     data-empty="true"
                     class="wa-editor"
                     style="display:block;"></div>
            </div>
        </div>

        {{-- ENTITY PANEL --}}
        <div class="wc" id="wa-ent-card" style="display:none;flex-direction:column;">
            <div class="wc-head between">
                <div style="display:flex;align-items:center;gap:.5rem;">
                    <div class="wc-icon wc-icon-purple"><i class="fas fa-list-ul"></i></div>
                    <div class="wc-title-stack">
                        <span class="wc-title">Entidades detectadas</span>
                        <span class="wc-sub" id="ent-count">0 entidades</span>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:.3rem;flex-wrap:wrap;">
                    <button class="btn-hdr btn-hdr-amber" id="btn-inicializar-personas" title="Escribe las iniciales de cada persona en el campo etiqueta de la tabla">
                        <i class="fas fa-id-badge"></i> Inicialar personas
                    </button>
                    <button class="btn-hdr btn-hdr-danger" id="btn-bulk-blacklist" style="display:none;">
                        <i class="fas fa-ban"></i> Agregar a Blacklist
                    </button>
                    <div class="prg-wrap" id="prg-anon" style="max-width:150px;">
                        <div class="prg-lbl">Anonimizando</div>
                        <div class="prg-bar"><div class="prg-fill prg-fill-green" id="prg-anon-fill"></div></div>
                    </div>
                    <button class="btn-hdr btn-hdr-green" id="btn-anonimizar">
                        <i class="fas fa-shield-halved"></i> Anonimizar
                    </button>
                </div>
            </div>
            <div class="ent-scroll" style="flex:1;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:.78rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width:28px;"><input type="checkbox" id="ent-cb-all" title="Seleccionar todas" style="cursor:pointer;"></th>
                                <th>Texto</th><th>Tipo</th>
                                <th class="text-center">N</th><th>Etiqueta</th>
                            </tr>
                        </thead>
                        <tbody id="ent-tbody"></tbody>
                    </table>
                </div>
            </div>
            <div class="wc-foot">
                <i class="fas fa-mouse-pointer me-1"></i>
                <strong>Click</strong> en entidad &rarr; siguiente ocurrencia &middot;
                <strong>Dbl-click</strong> en fila &rarr; primera ocurrencia &middot;
                <strong>Clic derecho</strong> en texto/entidad &rarr; etiquetar, whitelist, blacklist
            </div>
        </div>

    </div>{{-- /wa-main --}}

</div>

{{-- Format-error modal --}}
<div id="fmt-modal-overlay" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.48);align-items:center;justify-content:center;">
    <div style="background:var(--card-bg,#fff);border-radius:14px;box-shadow:0 8px 40px rgba(0,0,0,.22);max-width:380px;width:90%;padding:2rem 1.8rem;text-align:center;border:1px solid var(--card-border,#e2e8f0);">
        <div style="width:56px;height:56px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <i class="fas fa-file-circle-exclamation" style="font-size:1.6rem;color:#dc2626;"></i>
        </div>
        <h5 style="font-size:1rem;font-weight:700;color:var(--heading-color,#1e293b);margin-bottom:.4rem;">Formato no soportado</h5>
        <p style="font-size:.84rem;color:var(--muted-color,#64748b);margin-bottom:.25rem;">El archivo <strong id="fmt-modal-fname" style="color:var(--heading-color,#1e293b);word-break:break-all;"></strong> no puede procesarse.</p>
        <p style="font-size:.84rem;color:var(--muted-color,#64748b);margin-bottom:1.4rem;">Este anonimizador acepta únicamente archivos <strong>.doc</strong> y <strong>.docx</strong>.</p>
        <button id="fmt-modal-close" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:8px;padding:.55rem 1.6rem;font-size:.88rem;font-weight:600;cursor:pointer;transition:opacity .18s;">Entendido</button>
    </div>
</div>
{{-- Floating elements (body-level) --}}
<div id="wa-tip"></div>
<div id="wa-ctx"></div>
<div id="wa-toasts"></div>
@endsection

@push('scripts')
<script>
(function(){
'use strict';

/*  Config  */
const EC    = @json($entityColors);
const LMAP  = { PER:'PERSONA',PERSON:'PERSONA',ORG:'ORGANIZACIÓN',LOC:'LUGAR',GPE:'LUGAR',DATE:'FECHA',DNI:'DNI',EMAIL:'EMAIL',PHONE:'TELÉFONO',PATENTE:'PATENTE',MISC:'OTRO' };
const ENTITY_DEFS = [
    { code:'PER',  cls:'person',   label:'Persona' },
    { code:'ORG',  cls:'org',      label:'Organización' },
    { code:'LOC',  cls:'location', label:'Lugar' },
    { code:'DATE', cls:'date',     label:'Fecha' },
    { code:'DNI',  cls:'dni',      label:'DNI' },
    { code:'EMAIL',cls:'email',    label:'Email' },
    { code:'PHONE',cls:'phone',    label:'Teléfono' },
    { code:'MISC', cls:'misc',     label:'Otros' },
];
function labelToClass(code){ const d=ENTITY_DEFS.find(x=>x.code===code); return d?d.cls:'misc'; }
const ORDER = ['PERSONA','ORGANIZACIÓN','LUGAR','FECHA','DNI','EMAIL','TELÉFONO','OTRO'];
const WEXT  = ['.doc','.docx'];

// ── Source tracking: 'word' | 'pdf' | 'plain' ─────────────────────────────
let currentSource = 'plain';

/*  DOM refs  */
const $  = id => document.getElementById(id);
const waForm         = $('wa-form');
const wordInput      = $('word-input');
const dropZone       = $('drop-zone');
const filePreview    = $('file-preview');
const fpName         = $('fp-name');
const fpSize         = $('fp-size');
const btnRemove      = $('btn-remove');
const btnProcesar    = $('btn-procesar');
const prgUp          = $('prg-up');
const prgUpFill      = $('prg-up-fill');
// PDF panel
const waPdfForm      = $('wa-pdf-form');
const pdfInput       = $('pdf-input');
const pdfDropZone    = $('pdf-drop-zone');
const pdfPreview     = $('pdf-file-preview');
const pdfFpName      = $('pdf-fp-name');
const pdfFpSize      = $('pdf-fp-size');
const btnPdfRemove   = $('btn-pdf-remove');
const btnProcesarPdf = $('btn-procesar-pdf');
const prgPdfUp       = $('prg-pdf-up');
const prgPdfUpFill   = $('prg-pdf-up-fill');

const waEmpty     = $('wa-empty');
const waEditor    = $('wa-editor');
const sourceBadge = $('source-badge');
const btnAnalizar = $('btn-analizar');
const btnCopy     = $('btn-copy');
const btnDlTxt    = $('btn-dl-txt');
const btnClear    = $('btn-clear');
const prgAn       = $('prg-an');
const prgAnFill   = $('prg-an-fill');
const waMain      = $('wa-main');
const waEntCard   = $('wa-ent-card');
const entCount    = $('ent-count');
const entTbody    = $('ent-tbody');
const btnAnonimizar = $('btn-anonimizar');
const prgAnon     = $('prg-anon');
const prgAnonFill = $('prg-anon-fill');
const dlCard      = $('download-card');
const doneCard    = $('done-card');
const doneCardSub = $('done-card-sub');
const btnDlTxtDone= $('btn-dl-txt-done');
const tip         = $('wa-tip');
const ctx         = $('wa-ctx');
const toasts      = $('wa-toasts');

let curFile = 'texto-extraido.txt';
let lastFlash = null;
let ctxOpen = false;

/*  Utils  */
function fmtBytes(b){ return b<1048576?(b/1024).toFixed(1)+' KB':(b/1048576).toFixed(2)+' MB'; }
function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function csrf(){ return document.querySelector('meta[name="csrf-token"]')?.content??''; }
function isWord(f){ const n=f.name.toLowerCase(); return WEXT.some(e=>n.endsWith(e)); }
function showFmtModal(fname){
    const overlay = document.getElementById('fmt-modal-overlay');
    document.getElementById('fmt-modal-fname').textContent = fname ?? '';
    overlay.style.display = 'flex';
}
document.addEventListener('DOMContentLoaded', function(){
    const btn = document.getElementById('fmt-modal-close');
    const overlay = document.getElementById('fmt-modal-overlay');
    if(btn) btn.addEventListener('click', ()=>{ overlay.style.display='none'; });
    if(overlay) overlay.addEventListener('click', e=>{ if(e.target===overlay) overlay.style.display='none'; });
});
function spanText(s){ return Array.from(s.childNodes).filter(n=>n.nodeType===Node.TEXT_NODE).map(n=>n.textContent).join(''); }

function progress(wEl, fEl){
    let iv=null;
    return {
        start(){ wEl.classList.add('active'); fEl.style.width='0%'; let p=0; iv=setInterval(()=>{ if(p<86){p+=Math.random()*4; fEl.style.width=Math.min(p,86)+'%';} },300); },
        finish(){ clearInterval(iv); fEl.style.width='100%'; setTimeout(()=>{ wEl.classList.remove('active'); fEl.style.width='0%'; },500); }
    };
}

const pgUp    = progress(prgUp,    prgUpFill);
const pgPdfUp = progress(prgPdfUp, prgPdfUpFill);
const pgAn    = progress(prgAn,    prgAnFill);
const pgAnon  = progress(prgAnon,  prgAnonFill);

function toast(msg, type='i'){
    const icons = {s:'fa-circle-check',e:'fa-circle-exclamation',i:'fa-circle-info'};
    const el = document.createElement('div');
    el.className = `wa-toast t${type}`;
    el.innerHTML = `<i class="fas ${icons[type]||icons.i}"></i><span>${msg}</span>`;
    toasts.appendChild(el);
    setTimeout(()=>{ el.style.opacity='0'; el.style.transition='opacity .3s'; setTimeout(()=>el.remove(),320); }, 4800);
}

/*  Entity type switches  */
const etCbs = document.querySelectorAll('.et-cb');
const getTypes = () => Array.from(etCbs).filter(c=>c.checked).map(c=>c.dataset.entityType);

etCbs.forEach(cb=>{
    cb.addEventListener('change',()=>{
        document.querySelector(`.et-lbl[data-entity-type="${cb.dataset.entityType}"]`)
            ?.classList.toggle('disabled',!cb.checked);
    });
});
$('btn-all').addEventListener('click',e=>{ e.preventDefault(); etCbs.forEach(cb=>{ cb.checked=true; document.querySelector(`.et-lbl[data-entity-type="${cb.dataset.entityType}"]`)?.classList.remove('disabled'); }); });
$('btn-none').addEventListener('click',e=>{ e.preventDefault(); etCbs.forEach(cb=>{ cb.checked=false; document.querySelector(`.et-lbl[data-entity-type="${cb.dataset.entityType}"]`)?.classList.add('disabled'); }); });

/*  File input & drag-drop — WORD  */
function setPreview(file){
    curFile = file.name.replace(/\.(doc|docx)$/i,'') + '.txt';
    fpName.textContent = file.name; fpSize.textContent = fmtBytes(file.size);
    filePreview.classList.add('show'); btnProcesar.disabled = false;
    resetAll();
}
function clearFile(){
    wordInput.value=''; filePreview.classList.remove('show'); btnProcesar.disabled=true; curFile='texto-extraido.txt';
}
wordInput.addEventListener('change',()=>{ if(wordInput.files[0]) setPreview(wordInput.files[0]); });
btnRemove.addEventListener('click', clearFile);

dropZone.addEventListener('dragover',e=>{ e.preventDefault(); dropZone.classList.add('drag-over'); });
['dragleave','dragend'].forEach(ev=>dropZone.addEventListener(ev,()=>dropZone.classList.remove('drag-over')));
dropZone.addEventListener('drop',e=>{
    e.preventDefault(); dropZone.classList.remove('drag-over');
    const file=e.dataTransfer?.files[0]; if(!file) return;
    if(!isWord(file)){ showFmtModal(file.name); return; }
    const dt=new DataTransfer(); dt.items.add(file); wordInput.files=dt.files;
    setPreview(file);
});

/*  File input & drag-drop — PDF  */
function setPdfPreview(file){
    curFile = file.name.replace(/\.pdf$/i,'') + '.txt';
    pdfFpName.textContent = file.name; pdfFpSize.textContent = fmtBytes(file.size);
    pdfPreview.classList.add('show'); btnProcesarPdf.disabled = false;
    resetAll();
}
function clearPdfFile(){
    pdfInput.value=''; pdfPreview.classList.remove('show'); btnProcesarPdf.disabled=true;
}
pdfInput.addEventListener('change',()=>{ if(pdfInput.files[0]) setPdfPreview(pdfInput.files[0]); });
btnPdfRemove.addEventListener('click', clearPdfFile);

pdfDropZone.addEventListener('dragover',e=>{ e.preventDefault(); pdfDropZone.classList.add('drag-over'); });
['dragleave','dragend'].forEach(ev=>pdfDropZone.addEventListener(ev,()=>pdfDropZone.classList.remove('drag-over')));
pdfDropZone.addEventListener('drop',e=>{
    e.preventDefault(); pdfDropZone.classList.remove('drag-over');
    const file=e.dataTransfer?.files[0]; if(!file) return;
    if(!file.name.toLowerCase().endsWith('.pdf') && file.type!=='application/pdf'){
        toast('Solo se aceptan archivos PDF','e'); return;
    }
    const dt=new DataTransfer(); dt.items.add(file); pdfInput.files=dt.files;
    setPdfPreview(file);
});

/*  Editor state  */
function setSource(src, label){
    currentSource = src;
    if(sourceBadge){
        if(src !== 'plain' && label){
            sourceBadge.textContent = label;
            sourceBadge.style.display = 'inline';
        } else {
            sourceBadge.style.display = 'none';
        }
    }
}

function showEditorText(text){
    waEmpty.style.display='none'; waEditor.style.display='block';
    waEditor.contentEditable='true'; // temporarily to allow innerText assignment
    waEditor.innerText=text;
    waEditor.contentEditable='false'; // read-only after load
    syncEmpty(); enableBtns(true);
}
function showEditorHtml(html){
    waEmpty.style.display='none'; waEditor.style.display='block';
    waEditor.innerHTML=html;
    waEditor.contentEditable='false'; // read-only after analysis
    syncEmpty();
}
function syncEmpty(){
    const isEmpty = waEditor.innerText.trim()==='';
    waEditor.setAttribute('data-empty', isEmpty?'true':'false');
    // Hide/show the empty state banner
    if(!isEmpty){
        waEmpty.style.display='none';
        waEditor.style.display='block';
        enableBtns(true);
    } else {
        // Don't force hide — let user see the placeholder via CSS
        enableBtns(false);
    }
}
function enableBtns(on){ btnAnalizar.disabled=!on; btnCopy.disabled=!on; btnDlTxt.disabled=!on; btnClear.disabled=!on; }

// Intercept paste to keep only plain text
waEditor.addEventListener('paste', e => {
    e.preventDefault();
    const plain = (e.clipboardData || window.clipboardData).getData('text/plain');
    document.execCommand('insertText', false, plain);
    // If pasting into an empty editor, set source to plain
    if(currentSource === 'plain') setSource('plain', null);
    setTimeout(syncEmpty, 0);
});
waEditor.addEventListener('input', syncEmpty);

// On focus: if editor is empty and we had a banner, make sure editor fills space
waEditor.addEventListener('focus', ()=>{
    if(waEditor.getAttribute('data-empty') === 'true'){
        // user is about to type — hide empty banner and set source to plain
        waEmpty.style.display='none';
    }
});

function resetAll(){
    waEntCard.style.display='none'; waMain.classList.remove('split');
    // Keep the editor visible (always shown) — just clear content
    waEmpty.style.display=''; waEditor.style.display='block';
    waEditor.contentEditable='true'; // editable again so user can paste
    waEditor.innerHTML=''; waEditor.setAttribute('data-empty','true');
    enableBtns(false);
    dlCard.style.display='none'; doneCard.style.display='none';
    entTbody.innerHTML=''; lastFlash=null; hideTip(); closeCtx();
    setSource('plain', null);
}

function showEntityPanel(grouped){
    renderTable(grouped);
    entCount.textContent = grouped.length + ' entidades';
    waEntCard.style.display='flex'; waMain.classList.add('split');
}

/*  Process (upload Word)  */
waForm.addEventListener('submit', async e=>{
    e.preventDefault();
    if(!wordInput.files[0]){ toast('Seleccioná un archivo Word.','e'); return; }
    if(!isWord(wordInput.files[0])){ showFmtModal(wordInput.files[0].name); return; }

    btnProcesar.disabled=true; btnProcesar.innerHTML='<i class="fas fa-spinner fa-spin"></i> Procesando';
    pgUp.start(); resetAll();

    const fd=new FormData(); fd.append('word',wordInput.files[0]); fd.append('_token',csrf());
    try{
        const r = await fetch('{{ route("word-anonymizer.process") }}',{ method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':csrf(),'Accept':'application/json'},body:fd });
        const d = await r.json();
        if(!r.ok) throw new Error(d.error??d.message??'Error al procesar el archivo.');
        setSource('word', 'Word');
        showEditorText(d.text);
        toast('Texto extraído correctamente.','s');
    } catch(err){ toast(err.message,'e'); }
    finally{ pgUp.finish(); btnProcesar.disabled=false; btnProcesar.innerHTML='<i class="fas fa-file-export"></i> Procesar Word'; }
});

/*  Process (upload PDF)  */
waPdfForm.addEventListener('submit', async e=>{
    e.preventDefault();
    if(!pdfInput.files[0]){ toast('Seleccioná un archivo PDF.','e'); return; }

    btnProcesarPdf.disabled=true; btnProcesarPdf.innerHTML='<i class="fas fa-spinner fa-spin"></i> Procesando';
    pgPdfUp.start(); resetAll();

    const fd=new FormData(); fd.append('pdf',pdfInput.files[0]); fd.append('_token',csrf());
    try{
        const r = await fetch('{{ route("word-anonymizer.process-pdf") }}',{ method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':csrf(),'Accept':'application/json'},body:fd });
        const d = await r.json();
        if(!r.ok) throw new Error(d.error??d.message??'Error al procesar el PDF.');
        setSource('pdf', 'PDF');
        showEditorText(d.text);
        const mLabel = d.method==='ocr' ? 'Extraído vía OCR.' : 'Extraído vía texto nativo.';
        toast(mLabel, 's');
    } catch(err){ toast(err.message,'e'); }
    finally{ pgPdfUp.finish(); btnProcesarPdf.disabled=false; btnProcesarPdf.innerHTML='<i class="fas fa-file-export"></i> Procesar PDF'; }
});

/*  Header buttons  */
btnCopy.addEventListener('click', async()=>{
    const t=waEditor.innerText; if(!t.trim()) return;
    try{ await navigator.clipboard.writeText(t); } catch{ document.execCommand('copy'); }
    toast('Texto copiado.','s');
});
btnDlTxt.addEventListener('click',()=>{
    const t=waEditor.innerText; if(!t.trim()) return;
    const u=URL.createObjectURL(new Blob([t],{type:'text/plain;charset=utf-8'}));
    const a=Object.assign(document.createElement('a'),{href:u,download:curFile});
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(u);
    toast('Archivo descargado.','s');
});
btnClear.addEventListener('click',()=>{ clearFile(); clearPdfFile(); resetAll(); });

// Done-card download txt button
if(btnDlTxtDone) btnDlTxtDone.addEventListener('click',()=>{
    const t=waEditor.innerText; if(!t.trim()) return;
    const u=URL.createObjectURL(new Blob([t],{type:'text/plain;charset=utf-8'}));
    const a=Object.assign(document.createElement('a'),{href:u,download:curFile});
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(u);
    toast('Archivo descargado.','s');
});

/*  Analyze  */
btnAnalizar.addEventListener('click', async()=>{
    const text=waEditor.innerText.trim();
    if(!text||text.length<10){ toast('Ingresá al menos 10 caracteres.','e'); return; }
    const sel=getTypes(); if(!sel.length){ toast('Seleccioná al menos un tipo.','e'); return; }

    btnAnalizar.disabled=true; btnAnalizar.innerHTML='<i class="fas fa-spinner fa-spin"></i> Buscando';
    pgAn.start();
    waEntCard.style.display='none'; waMain.classList.remove('split'); dlCard.style.display='none'; lastFlash=null;

    const body=new URLSearchParams({_token:csrf(),text,entity_filter:sel.join(',')});
    try{
        const r = await fetch('{{ route("word-anonymizer.analyze") }}',{
            method:'POST',credentials:'same-origin',
            headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':csrf(),'Accept':'application/json'},
            body: body.toString()
        });
        const d = await r.json();
        if(!r.ok) throw new Error(d.error??d.message??'Error al analizar.');
        if(d.html) showEditorHtml(d.html);
        const grouped = d.groupedEntities||[];
        if(!grouped.length){ toast('No se detectaron entidades.','i'); }
        else{ showEntityPanel(grouped); toast(`${grouped.length} entidad/es detectadas.`,'s'); }
    } catch(err){ toast(err.message,'e'); }
    finally{ pgAn.finish(); btnAnalizar.disabled=false; btnAnalizar.innerHTML='<i class="fas fa-search"></i> Buscar Entidades'; }
});

/*  Entity table  */
function renderTable(grouped){
    const sorted=[...grouped].sort((a,b)=>{
        const da=LMAP[a.label]||a.label||'OTRO', db=LMAP[b.label]||b.label||'OTRO';
        const ia=ORDER.indexOf(da), ib=ORDER.indexOf(db);
        const ia2=ia<0?999:ia, ib2=ib<0?999:ib;
        return ia2!==ib2 ? ia2-ib2 : (a.text||'').localeCompare(b.text||'','es');
    });
    let html=''; let cur=null; const cnts={};
    for(const item of sorted){
        const dl=LMAP[item.label]||item.label||'OTRO';
        const col=EC[item.label]||'#ddd';
        if(dl!==cur){ html+=`<tr class="table-secondary"><td colspan="4" class="fw-semibold py-1" style="font-size:.73rem;">${esc(dl)}</td></tr>`; cur=dl; }
        cnts[dl]=(cnts[dl]||0)+1;
        const vj=esc(JSON.stringify(item.variants||[item.text]));
        html+=`<tr class="entity-row" data-entity-texts="${vj}" data-label="${esc(item.label)}" style="cursor:pointer;">
          <td class="py-1" style="width:28px;"><input type="checkbox" class="ent-row-cb" style="cursor:pointer;"></td>
          <td class="fw-medium py-1"><span class="ej-link" style="cursor:pointer;">${esc(item.text)}</span></td>
          <td class="py-1"><span class="badge rounded-pill" style="background:${col};color:#333;font-size:.67rem;">${esc(dl)}</span></td>
          <td class="text-center py-1"><span class="badge bg-secondary">${item.count}</span></td>
          <td class="py-1"><input type="text" class="form-control form-control-sm ent-lbl-in" value="${esc('['+dl+' '+cnts[dl]+']')}" style="min-width:120px;font-size:.75rem;padding:.18rem .4rem;"></td>
        </tr>`;
    }
    entTbody.innerHTML=html;
    bindRowEvents();
}

/*  Bulk Blacklist  */
const btnBulkBl = $('btn-bulk-blacklist');
const entCbAll  = $('ent-cb-all');
const btnInicializarPersonas = $('btn-inicializar-personas');

function updateBulkBlBtn(){
    const checked = document.querySelectorAll('#ent-tbody .ent-row-cb:checked');
    const all     = document.querySelectorAll('#ent-tbody .ent-row-cb');
    if(btnBulkBl) btnBulkBl.style.display = checked.length > 0 ? 'inline-flex' : 'none';
    if(entCbAll){
        entCbAll.indeterminate = checked.length > 0 && checked.length < all.length;
        entCbAll.checked = all.length > 0 && checked.length === all.length;
    }
}

if(entCbAll) entCbAll.addEventListener('change', ()=>{
    document.querySelectorAll('#ent-tbody .ent-row-cb').forEach(cb => cb.checked = entCbAll.checked);
    updateBulkBlBtn();
});

entTbody.addEventListener('change', e=>{
    if(e.target.classList.contains('ent-row-cb')) updateBulkBlBtn();
});

if(btnBulkBl) btnBulkBl.addEventListener('click', async ()=>{
    const checkedCbs = Array.from(document.querySelectorAll('#ent-tbody .ent-row-cb:checked'));
    if(!checkedCbs.length) return;
    const rows = checkedCbs.map(cb => cb.closest('.entity-row')).filter(Boolean);

    btnBulkBl.disabled = true;
    btnBulkBl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando';

    let added = 0;
    const failedTerms = [];
    for(const row of rows){
        let variants = [];
        try{ variants = JSON.parse(row.dataset.entityTexts||'[]').map(x=>(x||'').trim()).filter(Boolean); }catch{}
        const eText  = variants[0] || row.querySelector('.ej-link')?.textContent.trim() || '';
        const eLabel = row.dataset.label || '';
        if(!eText){ failedTerms.push({ term:'(vacío)', reason:'Texto de entidad vacío' }); continue; }
        try{
            const r = await apiFetch('{{ route("pdf-analyzer.add-blacklist") }}', {term:eText, entity_type:eLabel||null});
            let d = null;
            try { d = await r.json(); } catch(parseErr) {
                console.error('[Blacklist bulk] Respuesta no-JSON del servidor', r.status, eText);
                failedTerms.push({ term: eText, reason: `Error del servidor (HTTP ${r.status})` });
                continue;
            }
            if(!r.ok || !d.success){
                const reason = d?.message || d?.errors ? Object.values(d?.errors||{})[0]?.[0] : null;
                console.warn('[Blacklist bulk] Fallo al agregar:', eText, reason || r.status);
                failedTerms.push({ term: eText, reason: reason || `HTTP ${r.status}` });
                continue;
            }
            waEditor.querySelectorAll('.entity').forEach(s=>{
                if(variants.includes(spanText(s).trim()) && (s.dataset.label||'')===eLabel)
                    s.replaceWith(document.createTextNode(spanText(s)));
            });
            removeFromTable(eText, eLabel);
            added++;
        } catch(err){
            console.error('[Blacklist bulk] Excepción al agregar:', eText, err);
            failedTerms.push({ term: eText, reason: err.message || 'Error de red' });
        }
    }

    if(added)             toast(`${added} entidad/es agregadas a la Blacklist.`, 's');
    if(failedTerms.length){
        const names = failedTerms.map(f => `"${f.term}"`).join(', ');
        const reason = failedTerms.length === 1 ? ` — ${failedTerms[0].reason}` : '';
        toast(`${failedTerms.length} entidad/es no pudieron agregarse: ${names}${reason}`, 'e');
        console.error('[Blacklist bulk] Fallos detallados:', failedTerms);
    }

    btnBulkBl.disabled = false;
    btnBulkBl.innerHTML = '<i class="fas fa-ban"></i> Agregar a Blacklist';
    updateBulkBlBtn();
});

/*  Inicialar Personas  — escribe las iniciales en el campo etiqueta de cada fila PERSONA  */
if(btnInicializarPersonas) btnInicializarPersonas.addEventListener('click', async ()=>{
    // Recoger todas las filas de entidades tipo PERSONA
    const perRows = Array.from(document.querySelectorAll('.entity-row'))
        .filter(r => r.dataset.label === 'PER' || r.dataset.label === 'PERSON');

    if(!perRows.length){
        toast('No hay personas detectadas en la lista de entidades.','i');
        return;
    }

    // Mapear cada fila a su nombre principal y colectar todos los nombres únicos
    const names = [];
    const rowNameMap = new Map(); // row → nombre principal
    perRows.forEach(row=>{
        let variants = [];
        try{ variants = JSON.parse(row.dataset.entityTexts||'[]').map(x=>(x||'').trim()).filter(Boolean); }catch{}
        const primary = variants[0] || row.querySelector('.ej-link')?.textContent.trim() || '';
        if(primary) rowNameMap.set(row, primary);
        variants.forEach(n=>{ if(n && !names.includes(n)) names.push(n); });
    });

    if(!names.length){ toast('No se pudieron obtener los nombres de las personas.','e'); return; }

    btnInicializarPersonas.disabled = true;
    btnInicializarPersonas.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando';

    try{
        const r = await apiFetch('{{ route("word-anonymizer.initials") }}', { names });
        const d = await r.json();
        if(!r.ok) throw new Error(d.error??d.message??'Error al calcular iniciales.');

        const map = d.initials; // { "Juan Garcia": "J.G.", ... }

        // Escribir las iniciales en el campo etiqueta de cada fila PERSONA
        let updated = 0;
        for(const [row, primaryName] of rowNameMap.entries()){
            const initial = map[primaryName];
            if(!initial) continue;
            const input = row.querySelector('.ent-lbl-in');
            if(input){ input.value = initial; updated++; }
        }

        toast(`${updated} etiqueta/s actualizadas con iniciales.`, 's');

    }catch(err){ toast(err.message,'e'); }
    finally{
        btnInicializarPersonas.disabled = false;
        btnInicializarPersonas.innerHTML = '<i class="fas fa-id-badge"></i> Inicialar personas';
    }
});

/*  Navigation helpers  */
function findSpans(keys){
    const ks=Array.isArray(keys)?keys.map(v=>(v||'').trim()):[(keys||'').trim()];
    return Array.from(waEditor.querySelectorAll('.entity')).filter(s=>ks.includes(spanText(s).trim()));
}
function variantsForSpan(span){
    const t=spanText(span).trim();
    for(const row of document.querySelectorAll('.entity-row')){
        try{ const v=JSON.parse(row.dataset.entityTexts||'[]'); if(v.map(x=>(x||'').trim()).includes(t)) return v; } catch{}
    }
    return [t];
}
function labelForSpan(span){
    const t=spanText(span).trim();
    for(const row of document.querySelectorAll('.entity-row')){
        try{ const v=JSON.parse(row.dataset.entityTexts||'[]'); if(v.map(x=>(x||'').trim()).includes(t)) return LMAP[row.dataset.label]||row.dataset.label||''; } catch{}
    }
    const cm={person:'PERSONA',org:'ORGANIZACIÓN',location:'LUGAR',date:'FECHA',dni:'DNI',email:'EMAIL',phone:'TELÉFONO',misc:'OTRO'};
    for(const [k,v] of Object.entries(cm)) if(span.classList.contains(k)) return v;
    return '';
}
function scrollTo(span){
    const lh=Math.max(span.offsetHeight||18,18);
    waEditor.scrollTo({top:Math.max(0,span.offsetTop-lh),behavior:'smooth'});
    setTimeout(()=>{ waEditor.focus(); try{ const r=document.createRange(),s=window.getSelection(); r.setStartAfter(span); r.collapse(true); s.removeAllRanges(); s.addRange(r); }catch{} },120);
}
function flash(span,variants){
    if(lastFlash&&lastFlash!==span) lastFlash.classList.remove('entity-flash');
    findSpans(variants).forEach(s=>s.classList.remove('entity-flash'));
    span.classList.add('entity-flash'); lastFlash=span;
}

/*  Table row double-click  */
function bindRowEventsToRow(row){
    row.querySelector('.ent-row-cb')?.addEventListener('click', e => e.stopPropagation());
    row.addEventListener('dblclick',e=>{
        if(e.target.closest('.ent-lbl-in')||e.target.closest('.ent-row-cb')) return;
        let variants=[];
        try{ variants=JSON.parse(row.dataset.entityTexts||'[]'); } catch{ variants=[row.querySelector('.ej-link')?.textContent.trim()||'']; }
        const spans=findSpans(variants);
        if(!spans.length){ toast('No se encontró la entidad en el texto.','i'); return; }
        scrollTo(spans[0]); flash(spans[0],variants);
        row.classList.add('jumping'); setTimeout(()=>row.classList.remove('jumping'),700);
    });
}
function bindRowEvents(){
    document.querySelectorAll('.entity-row').forEach(row=>bindRowEventsToRow(row));
}

/*  Add a manually-tagged entity to the entity table  */
function addManualEntityToTable(text, label){
    const col  = EC[label] || '#ddd';
    const dl   = LMAP[label] || label || 'OTRO';

    // If this text+label already exists as a row, just increment count
    let existingRow = null;
    document.querySelectorAll('.entity-row').forEach(row=>{
        if(row.dataset.label !== label) return;
        let v=[]; try{ v=JSON.parse(row.dataset.entityTexts||'[]').map(x=>(x||'').trim()); }catch{}
        if(v.includes(text.trim())) existingRow=row;
    });
    if(existingRow){
        const cb=existingRow.querySelector('.badge.bg-secondary');
        if(cb) cb.textContent = String((parseInt(cb.textContent)||0)+1);
        return;
    }

    // Find existing section separator for this label type
    let sectionSep = null;
    document.querySelectorAll('#ent-tbody .table-secondary').forEach(sep=>{
        if(sep.querySelector('td')?.textContent.trim()===dl) sectionSep=sep;
    });

    const count = document.querySelectorAll(`#ent-tbody .entity-row[data-label="${label}"]`).length + 1;
    const newRow = document.createElement('tr');
    newRow.className='entity-row';
    newRow.dataset.entityTexts=JSON.stringify([text]);
    newRow.dataset.label=label;
    newRow.style.cursor='pointer';
    newRow.innerHTML=
        `<td class="py-1" style="width:28px;"><input type="checkbox" class="ent-row-cb" style="cursor:pointer;"></td>`+
        `<td class="fw-medium py-1"><span class="ej-link" style="cursor:pointer;">${esc(text)}</span></td>`+
        `<td class="py-1"><span class="badge rounded-pill" style="background:${col};color:#333;font-size:.67rem;">${esc(dl)}</span></td>`+
        `<td class="text-center py-1"><span class="badge bg-secondary">1</span></td>`+
        `<td class="py-1"><input type="text" class="form-control form-control-sm ent-lbl-in" value="${esc('['+dl+' '+count+']')}" style="min-width:120px;font-size:.75rem;padding:.18rem .4rem;"></td>`;

    if(sectionSep){
        // Insert after the last entity-row within this section
        let anchor=sectionSep;
        let nx=sectionSep.nextElementSibling;
        while(nx && !nx.classList.contains('table-secondary')){ anchor=nx; nx=nx.nextElementSibling; }
        anchor.after(newRow);
    } else {
        // Create a new section header and append to end
        const sep=document.createElement('tr');
        sep.className='table-secondary';
        sep.innerHTML=`<td colspan="4" class="fw-semibold py-1" style="font-size:.73rem;">${esc(dl)}</td>`;
        entTbody.appendChild(sep);
        entTbody.appendChild(newRow);
    }

    bindRowEventsToRow(newRow);
    entCount.textContent=document.querySelectorAll('#ent-tbody .entity-row').length+' entidades';
    // Show entity panel if not visible
    waEntCard.style.display='flex';
    waMain.classList.add('split');
}

/*  Editor click: cycle occurrences  */
waEditor.addEventListener('click',e=>{
    const span=e.target.closest('.entity'); if(!span) return;
    const v=variantsForSpan(span); const spans=findSpans(v);
    // Si es la única ocurrencia, no hacer nada (evita desplazamiento innecesario)
    if(spans.length <= 1){ e.stopPropagation(); return; }
    const idx=spans.indexOf(span);
    const next = idx>=0 && idx<spans.length-1 ? spans[idx+1] : spans[0];
    if(next){ scrollTo(next); flash(next,v); }
    e.stopPropagation();
});

/*  Tooltip  */
function hideTip(){ tip.style.display='none'; }
function showTip(span,e){
    const v=variantsForSpan(span); const spans=findSpans(v); const idx=spans.indexOf(span);
    const lbl=labelForSpan(span); const occ=idx>=0?`${idx+1} de ${spans.length}`:'';
    tip.textContent=[lbl,occ].filter(Boolean).join('  ');
    tip.style.display='block'; posTip(e);
}
function posTip(e){
    const tw=tip.offsetWidth, th=tip.offsetHeight;
    let x=e.clientX-tw/2, y=e.clientY-th-9;
    x=Math.max(6,Math.min(x,window.innerWidth-tw-6));
    if(y<6) y=e.clientY+16;
    tip.style.left=x+'px'; tip.style.top=y+'px';
}
waEditor.addEventListener('mouseover',e=>{ const s=e.target.closest('.entity'); if(!s){hideTip();return;} s.classList.add('entity-hover'); showTip(s,e); });
waEditor.addEventListener('mousemove',e=>{ const s=e.target.closest('.entity'); if(!s){hideTip();return;} posTip(e); });
waEditor.addEventListener('mouseout', e=>{ const s=e.target.closest('.entity'); if(!s) return; s.classList.remove('entity-hover'); hideTip(); });

/*  Context menu  */
function closeCtx(){ ctx.style.display='none'; ctx.innerHTML=''; ctxOpen=false; }
function openCtx(x,y,html){
    ctx.innerHTML=html; ctx.style.display='block'; ctxOpen=true;
    const mw=ctx.offsetWidth||220, mh=ctx.offsetHeight||100;
    let cx=Math.min(x,window.innerWidth-mw-8), cy=y+6;
    if(cy+mh>window.innerHeight-8) cy=y-mh-6;
    ctx.style.left=cx+'px'; ctx.style.top=cy+'px';
}
document.addEventListener('click',e=>{ if(ctxOpen&&!ctx.contains(e.target)) closeCtx(); });

function apiFetch(url,body){
    return fetch(url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'},body:JSON.stringify(body)});
}
function removeFromTable(eText,eLabel){
    let removed=0;
    document.querySelectorAll('.entity-row').forEach(row=>{
        let v=[]; try{ v=JSON.parse(row.dataset.entityTexts||'[]').map(x=>(x||'').trim()); }catch{}
        if(v.includes(eText.trim())&&(row.dataset.label||'')===eLabel){ row.remove(); removed++; }
    });
    document.querySelectorAll('#ent-tbody .table-secondary').forEach(sep=>{
        let next=sep.nextElementSibling,has=false;
        while(next&&!next.classList.contains('table-secondary')){ if(next.classList.contains('entity-row')){has=true;break;} next=next.nextElementSibling; }
        if(!has) sep.remove();
    });
    if(removed>0) entCount.textContent=document.querySelectorAll('#ent-tbody .entity-row').length+' entidades';
}

document.addEventListener('contextmenu',e=>{
    closeCtx(); hideTip();

    // ── Entity table row right-click ──────────────────────────────────────────
    const row=e.target.closest('#ent-tbody .entity-row');
    if(row){
        e.preventDefault(); e.stopPropagation();
        let variants=[]; try{ variants=JSON.parse(row.dataset.entityTexts||'[]').map(x=>(x||'').trim()).filter(Boolean); }catch{}
        const eText=variants[0]||row.querySelector('.ej-link')?.textContent.trim()||'';
        const eLabel=row.dataset.label||'';
        if(!eText){ closeCtx(); return; }
        const pv=eText.length>38?eText.slice(0,38)+'…':eText;
        openCtx(e.clientX,e.clientY,`
            <div class="ctx-head">Entidad</div>
            <div class="ctx-preview">${esc(pv)}</div><hr>
            <button data-a="ign"><i class="fas fa-eye-slash me-2 text-muted"></i>Ignorar sólo esta vez</button>
            <button data-a="bl2"><i class="fas fa-ban me-2 text-danger"></i>Ignorar y agregar a Blacklist</button>
        `);
        ctx.querySelector('[data-a="ign"]').addEventListener('click',ev=>{
            ev.stopPropagation();
            waEditor.querySelectorAll('.entity').forEach(s=>{ if(variants.includes(spanText(s).trim())&&(s.dataset.label||'')===eLabel) s.replaceWith(document.createTextNode(spanText(s))); });
            removeFromTable(eText,eLabel); closeCtx(); toast('Entidad ignorada.','i');
        },{once:true});
        ctx.querySelector('[data-a="bl2"]').addEventListener('click',async ev=>{
            ev.stopPropagation(); const btn=ev.currentTarget; btn.classList.add('loading'); btn.textContent=' Guardando';
            try{
                const r=await apiFetch('{{ route("pdf-analyzer.add-blacklist") }}',{term:eText,entity_type:eLabel||null});
                const d=await r.json(); if(!r.ok||!d.success) throw new Error(d.message||'Error');
                waEditor.querySelectorAll('.entity').forEach(s=>{ if(variants.includes(spanText(s).trim())&&(s.dataset.label||'')===eLabel) s.replaceWith(document.createTextNode(spanText(s))); });
                removeFromTable(eText,eLabel); toast(d.message,'s');
            } catch(err){ toast(' '+err.message,'e'); } finally{ closeCtx(); }
        },{once:true});
        return;
    }

    // ── Editor / selection context menu ──────────────────────────────────────
    const span=e.target.closest('.entity');
    const inEd=waEditor.contains(e.target);
    if(!inEd&&!span) return;
    const selText=(window.getSelection()?.toString()||'').trim();

    if(selText&&!span&&inEd){
        e.preventDefault();
        // Save the current selection range BEFORE ctx takes focus
        const sel=window.getSelection();
        const savedRange = sel&&sel.rangeCount>0 ? sel.getRangeAt(0).cloneRange() : null;
        const pv=selText.length>38?selText.slice(0,38)+'…':selText;
        openCtx(e.clientX,e.clientY,`
            <div class="ctx-head">Texto seleccionado</div>
            <div class="ctx-preview">"${esc(pv)}"</div><hr>
            <button data-a="wl"><i class="fas fa-list-check me-2 text-success"></i>Agregar a Whitelist</button>
            <button data-a="bl"><i class="fas fa-ban me-2 text-danger"></i>Agregar a Blacklist</button>
            <button data-a="ent"><i class="fas fa-tag me-2 text-primary"></i>Agregar como entidad…</button>
        `);
        ctx.querySelector('[data-a="wl"]').addEventListener('click',async ev=>{
            ev.stopPropagation(); const btn=ev.currentTarget; btn.classList.add('loading'); btn.textContent=' Guardando';
            try{ const r=await apiFetch('{{ route("pdf-analyzer.add-whitelist") }}',{term:selText,entity_type:null}); const d=await r.json(); if(!r.ok||!d.success) throw new Error(d.message||'Error'); toast(d.message,'s'); }
            catch(err){ toast(' '+err.message,'e'); } finally{ closeCtx(); }
        },{once:true});
        ctx.querySelector('[data-a="bl"]').addEventListener('click',async ev=>{
            ev.stopPropagation(); const btn=ev.currentTarget; btn.classList.add('loading'); btn.textContent=' Guardando';
            try{ const r=await apiFetch('{{ route("pdf-analyzer.add-blacklist") }}',{term:selText,entity_type:null}); const d=await r.json(); if(!r.ok||!d.success) throw new Error(d.message||'Error'); toast(d.message,'s'); }
            catch(err){ toast(' '+err.message,'e'); } finally{ closeCtx(); }
        },{once:true});
        ctx.querySelector('[data-a="ent"]').addEventListener('click', ev=>{
            ev.stopPropagation();
            // Replace ctx content with entity type picker
            const typeBtns = ENTITY_DEFS.map(d=>
                `<button class="ctx-type-opt" data-code="${d.code}">`+
                `<span class="ctx-type-dot" style="background:${EC[d.code]||d.color||'#ddd'};"></span>${d.label}</button>`
            ).join('');
            ctx.innerHTML=
                `<div class="ctx-head">Tipo de entidad</div>`+
                `<div class="ctx-preview">"${esc(pv)}"</div><hr>`+
                `<div class="ctx-type-grid">${typeBtns}</div>`;
            // Reposition in case size changed
            const mw=ctx.offsetWidth||220, mh=ctx.offsetHeight||160;
            const cx=Math.min(parseInt(ctx.style.left),window.innerWidth-mw-8);
            const cy=Math.min(parseInt(ctx.style.top),window.innerHeight-mh-8);
            ctx.style.left=cx+'px'; ctx.style.top=cy+'px';

            ctx.querySelectorAll('.ctx-type-opt').forEach(btn=>{
                btn.addEventListener('click', ev2=>{
                    ev2.stopPropagation();
                    const code=btn.dataset.code;
                    const def=ENTITY_DEFS.find(x=>x.code===code);
                    if(!def||!savedRange) { closeCtx(); return; }
                    const color=EC[code]||'#ddd';
                    // Wrap the saved range with an entity span
                    try{
                        const span=document.createElement('span');
                        span.className=`entity ${def.cls}`;
                        span.dataset.label=code;
                        span.title=code;
                        span.style.background=color;
                        savedRange.surroundContents(span);
                    } catch(_){
                        // surroundContents fails if range crosses elements → use extract+wrap
                        try{
                            const span=document.createElement('span');
                            span.className=`entity ${def.cls}`;
                            span.dataset.label=code;
                            span.title=code;
                            span.style.background=color;
                            span.appendChild(savedRange.extractContents());
                            savedRange.insertNode(span);
                        } catch(e2){ toast('No se pudo etiquetar el texto seleccionado.','e'); closeCtx(); return; }
                    }
                    // Add to entity table
                    addManualEntityToTable(selText, code);
                    closeCtx();
                    toast(`"${selText.length>30?selText.slice(0,30)+'…':selText}" etiquetado como ${def.label}.`,'s');
                },{once:true});
            });
        },{once:true});
        e.stopPropagation(); return;
    }

    if(!span) return;
    e.preventDefault();
    const eText=spanText(span).trim(), eLabel=span.dataset.label||'';
    openCtx(e.clientX,e.clientY,`
        <div class="ctx-head">Entidad</div>
        <div class="ctx-preview">${esc(eText)}</div><hr>
        <button data-a="ign"><i class="fas fa-eye-slash me-2 text-muted"></i>Ignorar sólo esta vez</button>
        <button data-a="bl2"><i class="fas fa-ban me-2 text-danger"></i>Ignorar y agregar a Blacklist</button>
    `);
    ctx.querySelector('[data-a="ign"]').addEventListener('click',ev=>{
        ev.stopPropagation();
        waEditor.querySelectorAll('.entity').forEach(s=>{ if(spanText(s).trim()===eText&&(s.dataset.label||'')===eLabel) s.replaceWith(document.createTextNode(spanText(s))); });
        removeFromTable(eText,eLabel); closeCtx(); toast('Entidad ignorada.','i');
    },{once:true});
    ctx.querySelector('[data-a="bl2"]').addEventListener('click',async ev=>{
        ev.stopPropagation(); const btn=ev.currentTarget; btn.classList.add('loading'); btn.textContent=' Guardando';
        try{
            const r=await apiFetch('{{ route("pdf-analyzer.add-blacklist") }}',{term:eText,entity_type:eLabel||null});
            const d=await r.json(); if(!r.ok||!d.success) throw new Error(d.message||'Error');
            waEditor.querySelectorAll('.entity').forEach(s=>{ if(spanText(s).trim()===eText&&(s.dataset.label||'')===eLabel) s.replaceWith(document.createTextNode(spanText(s))); });
            removeFromTable(eText,eLabel); toast(d.message,'s');
        } catch(err){ toast(' '+err.message,'e'); } finally{ closeCtx(); }
    },{once:true});
    e.stopPropagation();
});

/*  Anonymize  */
btnAnonimizar.addEventListener('click', async()=>{
    const rows=Array.from(document.querySelectorAll('.entity-row'));
    if(!rows.length){ toast('No hay entidades para anonimizar.','e'); return; }

    const replacements={};
    for(const row of rows){
        const lbl=(row.querySelector('.ent-lbl-in')?.value||'').trim(); if(!lbl) continue;
        let v=[]; try{ v=JSON.parse(row.dataset.entityTexts||'[]').map(x=>(x||'').trim()).filter(Boolean); }catch{}
        for(const t of v) if(t) replacements[t]=lbl;
    }
    if(!Object.keys(replacements).length){ toast('Completá al menos una etiqueta.','e'); return; }

    btnAnonimizar.disabled=true; btnAnonimizar.innerHTML='<i class="fas fa-spinner fa-spin"></i> Generando';
    pgAnon.start(); dlCard.style.display='none'; doneCard.style.display='none';

    // Visual replacement in editor
    for(const row of rows){
        const lbl=(row.querySelector('.ent-lbl-in')?.value||'').trim(); if(!lbl) continue;
        let v=[]; try{ v=JSON.parse(row.dataset.entityTexts||'[]').map(x=>(x||'').trim()).filter(Boolean); }catch{}
        Array.from(waEditor.querySelectorAll('.entity')).forEach(s=>{ if(v.includes(spanText(s).trim())) s.replaceWith(document.createTextNode(lbl)); });
        // Replace in plain text nodes too
        const walker=document.createTreeWalker(waEditor,NodeFilter.SHOW_TEXT,null);
        const nodes=[]; while(walker.nextNode()) nodes.push(walker.currentNode);
        for(const tn of nodes){
            if(!tn.parentNode) continue;
            let txt=tn.nodeValue; let changed=false;
            for(const t of v){ if(t&&txt.includes(t)){ txt=txt.split(t).join(lbl); changed=true; } }
            if(changed) tn.nodeValue=txt;
        }
        await new Promise(r=>setTimeout(r,28));
        row.style.background='var(--table-hover-bg)'; setTimeout(()=>{ row.style.background=''; },400);
    }

    // Final cleanup: strip any remaining entity spans so no colored backgrounds remain
    waEditor.querySelectorAll('.entity').forEach(s => {
        s.replaceWith(document.createTextNode(spanText(s)));
    });

    try{
        const r=await fetch('{{ route("word-anonymizer.anonymize") }}',{
            method:'POST',credentials:'same-origin',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'Accept':'application/json'},
            body:JSON.stringify({replacements})
        });
        const d=await r.json();
        if(!r.ok) throw new Error(d.error??d.message??'Error al generar el documento.');
        waEntCard.style.display='none'; waMain.classList.remove('split');
        // Show the right completion card based on source type
        if(d.source_type === 'word' && d.download_url){
            dlCard.style.display='block'; dlCard.scrollIntoView({behavior:'smooth',block:'nearest'});
            toast('Documento Word anonimizado listo para descargar.','s');
        } else {
            // PDF or plain text source
            const srcLabel = currentSource==='pdf' ? 'PDF' : 'texto plano';
            if(doneCardSub) doneCardSub.textContent = `Texto anonimizado (${srcLabel})`;
            doneCard.style.display='block'; doneCard.scrollIntoView({behavior:'smooth',block:'nearest'});
            toast('Texto anonimizado correctamente.','s');
        }
    } catch(err){ toast(err.message,'e'); }
    finally{ pgAnon.finish(); btnAnonimizar.disabled=false; btnAnonimizar.innerHTML='<i class="fas fa-shield-halved"></i> Anonimizar'; }
});

})();
</script>
@endpush