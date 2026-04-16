@extends('layouts.dashboard')

@section('title', 'Analizador de PDF — Alfa')
@section('page-title', 'Análisis y anonimización de documentos PDF')
@section('breadcrumb', 'Análisis de PDF')

@push('styles')
<style>
    /* ── Entidades ───────────────────────────────────────── */
    .entity {
        padding: 2px 5px;
        border-radius: 4px;
        font-weight: 500;
        cursor: pointer;
        position: relative;
        display: inline;
    }
    .entity.person   { background: {{ $entityColors['PER'] ?? '#ffcccc' }}; }
    .entity.org      { background: {{ $entityColors['ORG'] ?? '#cce5ff' }}; }
    .entity.location { background: {{ $entityColors['LOC'] ?? '#ccffcc' }}; }
    .entity.date     { background: {{ $entityColors['DATE'] ?? '#ffe0b3' }}; }
    .entity.dni      { background: {{ $entityColors['DNI'] ?? '#e0e0e0' }}; }
    .entity.email    { background: {{ $entityColors['EMAIL'] ?? '#ccf2ff' }}; }
    .entity.phone    { background: {{ $entityColors['PHONE'] ?? '#ffffcc' }}; }
    .entity.misc     { background: {{ $entityColors['MISC'] ?? '#e0ccff' }}; }

    /* ── Menú flotante al hacer clic derecho ─────────────── */
    .entity-menu {
        display: none;
        position: absolute;
        z-index: 9999;
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #ccc);
        border-radius: 6px;
        box-shadow: 0 4px 16px rgba(0,0,0,.18);
        padding: 4px 0;
        width: max-content;
        bottom: calc(100% + 5px);
        top: auto;
        left: 50%;
        transform: translateX(-50%);
    }
    /* Flecha decorativa — apunta hacia abajo cuando el menú está encima (dir=up) */
    .entity-menu[data-dir="up"]::after,
    .entity-menu:not([data-dir])::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: var(--card-border, #ccc);
    }
    .entity-menu[data-dir="up"]::before,
    .entity-menu:not([data-dir])::before {
        content: '';
        position: absolute;
        top: calc(100% + 1px);
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: var(--card-bg, #fff);
        z-index: 1;
    }
    /* Flecha apunta hacia arriba cuando el menú está debajo (dir=down) */
    .entity-menu[data-dir="down"]::after {
        content: '';
        position: absolute;
        bottom: 100%;
        top: auto;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-bottom-color: var(--card-border, #ccc);
    }
    .entity-menu[data-dir="down"]::before {
        content: '';
        position: absolute;
        bottom: calc(100% + 1px);
        top: auto;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-bottom-color: var(--card-bg, #fff);
        z-index: 1;
    }
    .entity-menu button {
        display: block;
        width: 100%;
        padding: 7px 16px;
        background: none;
        border: none;
        text-align: left;
        font-size: .84rem;
        cursor: pointer;
        white-space: nowrap;
        color: var(--body-color, #333);
    }
    .entity-menu button:hover { background: var(--table-hover-bg, #f0f4ff); }
    /* Separador visual entre opciones del menú */
    .entity-menu hr {
        margin: 3px 0;
        border-color: var(--card-border, #eee);
    }
    /* Estado de carga del botón blacklist */
    .entity-menu button.loading {
        opacity: .6;
        pointer-events: none;
    }

    /* ── Fila deshabilitada en tabla de entidades ────────── */
    .entity-row-disabled td { opacity: .38; }
    .entity-row-disabled input,
    .entity-row-disabled button { pointer-events: none !important; }
    .entity-row-disabled { background: var(--badge-light-bg, #f8f8f8) !important; }

    /* ── Editor ─────────────────────────────────────────── */
    #editor-container {
        border: 1px solid var(--input-border, #dee2e6);
        border-radius: .375rem;
        min-height: 420px;
        max-height: 620px;
        overflow-y: auto;
        padding: 1rem 1.25rem;
        background: var(--input-bg, #fefefe);
        line-height: 1.8;
        font-size: .95rem;
        max-width: 100ch;
        overflow-wrap: break-word;
        word-break: break-word;
        color: var(--body-color, #1e293b);
    }
    /* Placeholder cuando el editor está vacío */
    #editor-container[data-empty="true"]::before {
        content: attr(data-placeholder);
        color: var(--input-placeholder, #bbb);
        font-style: italic;
        pointer-events: none;
        display: block;
    }

    /* ── Leyenda ─────────────────────────────────────────── */
    .legend-dot {
        display: inline-block;
        width: 14px;
        height: 14px;
        border-radius: 3px;
        margin-right: 5px;
        vertical-align: middle;
    }

    /* ── Entity type switches ────────────────────────────── */
    .entity-type-row {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .25rem 0;
    }
    .entity-type-switch {
        position: relative;
        width: 32px;
        height: 18px;
        flex-shrink: 0;
    }
    .entity-type-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .entity-type-slider {
        position: absolute;
        inset: 0;
        background: #ccc;
        border-radius: 18px;
        cursor: pointer;
        transition: background .2s;
    }
    .entity-type-slider::before {
        content: '';
        position: absolute;
        width: 14px;
        height: 14px;
        left: 2px;
        bottom: 2px;
        background: #fff;
        border-radius: 50%;
        transition: transform .2s;
    }
    .entity-type-switch input:checked + .entity-type-slider {
        background: var(--accent, #6c5ce7);
    }
    .entity-type-switch input:checked + .entity-type-slider::before {
        transform: translateX(14px);
    }
    .entity-type-label {
        font-size: .82rem;
        cursor: pointer;
        user-select: none;
    }
    .entity-type-label.disabled {
        opacity: .4;
        text-decoration: line-through;
    }
    /* Mantener el panel de texto fijo y hacer la lista de entidades scrollable */
    .entities-scroll {
        max-height: 48vh;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    /* Make the entity text look like plain text (no hyperlink hint) */
    .entity-jump-link {
        color: inherit;
        text-decoration: none;
        cursor: default;
        font-weight: inherit;
    }
    .entity-jump-link:hover { text-decoration: none; }

    /* Strong fluorescent highlight for quick locating */
    .entity-flash {
        background: #ccff00 !important;
        color: #000 !important;
        box-shadow: 0 0 18px rgba(204,255,0,0.9);
        border-radius: 4px;
        padding: 0 3px;
        transition: box-shadow 120ms ease-in-out;
    }
    /* Hover style (separate class so it doesn't collide with programmatic flashes) */
    .entity-hover {
        background: #ccff00 !important;
        color: #000 !important;
        box-shadow: 0 0 12px rgba(204,255,0,0.85);
        border-radius: 4px;
        padding: 0 3px;
    }
    /* (Removed) Justified helper removed — feature deprecated */

    /* ── 80vh panels ─────────────────────────────────────── */
    .row.g-3 > .col-lg-3,
    .row.g-3 > .col-lg-9 {
        min-height: 80vh;
        display: flex;
        flex-direction: column;
    }
    .row.g-3 > .col-lg-9 > .card {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .row.g-3 > .col-lg-9 > .card > .card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    #editor-container {
        flex: 1;
        min-height: 300px;
    }
</style>
@endpush

@section('content')

{{-- ── Errores ──────────────────────────────────────────────────────────── --}}
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-3">

    {{-- ── Col izquierda: Formulario + Leyenda ─────────────────────────── --}}
    <div class="col-lg-3">

        {{-- Subida de archivo --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">
                <i class="fas fa-file-pdf me-2 text-danger"></i>Subir documento
            </div>
            <div class="card-body">
                <form method="POST"
                      action="{{ route('pdf-analyzer.process') }}"
                      enctype="multipart/form-data"
                      id="uploadForm">
                    @csrf
                    <input type="hidden" name="entity_filter" id="uploadEntityFilterInput">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Archivo PDF (máx. 20 MB)</label>
                        <input type="file"
                               name="pdf"
                               accept=".pdf"
                               class="form-control form-control-sm"
                               required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100" id="btnAnalizar">
                        <i class="fas fa-search me-1"></i>Analizar documento
                    </button>
                </form>
            </div>
        </div>

        {{-- Tipos de entidades (con switches para filtrar) --}}
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">
                <i class="fas fa-palette me-2"></i>Tipos de entidades
            </div>
            <div class="card-body p-2">
                <table class="table table-sm table-borderless mb-0" style="font-size:.82rem;">
                    @php
                        $entityTypesList = [
                            ['type' => 'PER',   'css' => 'person',   'label' => 'Persona'],
                            ['type' => 'ORG',   'css' => 'org',      'label' => 'Organización'],
                            ['type' => 'LOC',   'css' => 'location', 'label' => 'Lugar'],
                            ['type' => 'DATE',  'css' => 'date',     'label' => 'Fecha'],
                            ['type' => 'DNI',   'css' => 'dni',      'label' => 'DNI'],
                            ['type' => 'EMAIL', 'css' => 'email',    'label' => 'Email'],
                            ['type' => 'PHONE', 'css' => 'phone',    'label' => 'Teléfono'],
                            ['type' => 'MISC',  'css' => 'misc',     'label' => 'Otros'],
                        ];
                    @endphp
                    @foreach($entityTypesList as $et)
                    <tr>
                        <td class="entity-type-row">
                            <label class="entity-type-switch" title="Activar/desactivar {{ $et['label'] }}">
                                <input type="checkbox"
                                       class="entity-type-checkbox"
                                       data-entity-type="{{ $et['type'] }}"
                                       checked>
                                <span class="entity-type-slider"></span>
                            </label>
                            <span class="legend-dot" style="background:{{ $entityColors[$et['type']] ?? '#ddd' }}"></span>
                            <span class="entity-type-label" data-entity-type="{{ $et['type'] }}">{{ $et['label'] }}</span>
                        </td>
                    </tr>
                    @endforeach
                </table>
                <div class="d-flex gap-2 mt-2" style="font-size:.72rem;">
                    <a href="#" id="btnSelectAll" class="text-decoration-none">Todas</a>
                    <span class="text-muted">|</span>
                    <a href="#" id="btnSelectNone" class="text-decoration-none">Ninguna</a>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Col derecha: Editor + Botones ───────────────────────────────── --}}
    <div class="col-lg-9">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold">
                    <i class="fas fa-file-alt me-2"></i>Texto analizado
                </span>
                <div class="d-flex gap-2 flex-wrap">
                    <!-- Justify feature removed -->
                    <button class="btn btn-primary btn-sm" id="btnAnalizeText">
                        <i class="fas fa-search me-1"></i>Analizar texto
                    </button>
                    @if(isset($analyzedHtml))
                    {{-- Export removed per UI update --}}
                    @endif
                </div>
            </div>

            <div class="card-body">
                {{-- Editor siempre visible: acepta texto pegado o muestra resultado del análisis --}}
                <div id="editor-container"
                     contenteditable="true"
                     data-placeholder="Pegue aquí el texto a analizar, o suba un PDF desde el panel izquierdo…"
                     data-empty="{{ isset($analyzedHtml) ? 'false' : 'true' }}">@if(isset($analyzedHtml)){!! $analyzedHtml !!}@endif</div>

                    {{-- Export form removed (export button removed) --}}

                {{-- Formulario oculto para analizar texto pegado --}}
                <form method="POST"
                      action="{{ route('pdf-analyzer.analyze-text') }}"
                      id="analyzeTextForm">
                    @csrf
                    <textarea name="text" id="analyzeTextInput" style="display:none"></textarea>
                    <input type="hidden" name="entity_filter" id="entityFilterInput">
                </form>
            </div>
        </div>

        {{-- Estadísticas agrupadas de entidades --}}
        @if(isset($groupedEntities) && count($groupedEntities))
        <div class="card shadow-sm mt-3">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <div><i class="fas fa-list-ul me-2"></i>Entidades detectadas <span class="fw-normal text-muted">(agrupadas)</span></div>
                <span class="badge bg-secondary" id="entity-count-badge">{{ count($groupedEntities) }} entidades detectadas</span>
            </div>
            <div class="card-body p-2 entities-scroll">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Texto detectado</th>
                                <th>Tipo</th>
                                <th class="text-center">Veces</th>
                                <th>Etiqueta a usar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Map raw labels to Spanish display names (keep in sync with JS LABEL_MAP)
                                $labelMap = [
                                    'PER'     => 'PERSONA',
                                    'PERSON'  => 'PERSONA',
                                    'ORG'     => 'ORGANIZACIÓN',
                                    'LOC'     => 'LUGAR',
                                    'GPE'     => 'LUGAR',
                                    'DATE'    => 'FECHA',
                                    'DNI'     => 'DNI',
                                    'EMAIL'   => 'EMAIL',
                                    'PHONE'   => 'TELÉFONO',
                                    'PATENTE' => 'PATENTE',
                                    'MISC'    => 'OTRO',
                                ];

                                // Clone and sort using the same order as the legend (Persona, Organización, ...)
                                $order = [
                                    'PERSONA',
                                    'ORGANIZACIÓN',
                                    'LUGAR',
                                    'FECHA',
                                    'DNI',
                                    'EMAIL',
                                    'TELÉFONO',
                                    'OTRO',
                                ];

                                $entities = $groupedEntities;
                                usort($entities, function($a, $b) use ($labelMap, $order) {
                                    $da = $labelMap[$a['label'] ?? ''] ?? ($a['label'] ?? '');
                                    $db = $labelMap[$b['label'] ?? ''] ?? ($b['label'] ?? '');

                                    $ia = array_search($da, $order, true);
                                    $ib = array_search($db, $order, true);
                                    $ia = ($ia === false) ? PHP_INT_MAX : $ia;
                                    $ib = ($ib === false) ? PHP_INT_MAX : $ib;

                                    if ($ia !== $ib) return $ia <=> $ib;

                                    // Same group order — sort by display name then by text
                                    $c = strcasecmp($da, $db);
                                    if ($c === 0) {
                                        return strcasecmp($a['text'] ?? '', $b['text'] ?? '');
                                    }
                                    return $c;
                                });
                                $currentLabel = null;
                            @endphp

                            @php $counters = [];
                            @endphp
                            @foreach($entities as $item)
                                @php $label = $item['label'] ?? ''; @endphp

                                {{-- Separator row when label/type changes (use Spanish display names) --}}
                                @php $displayLabel = $labelMap[$label] ?? ($label ?: 'OTROS'); @endphp
                                @if($displayLabel !== ($currentLabel ? ($labelMap[$currentLabel] ?? $currentLabel) : $currentLabel))
                                    <tr class="table-secondary">
                                        <td colspan="4" class="fw-semibold">{{ $displayLabel }}</td>
                                    </tr>
                                    @php $currentLabel = $label; @endphp
                                @endif

                                @php
                                    $baseLabel = $displayLabel;
                                    $counters[$baseLabel] = ($counters[$baseLabel] ?? 0) + 1;
                                    $correlative = $counters[$baseLabel];
                                @endphp

                                <tr class="entity-row"
                                    data-entity-texts="{{ json_encode($item['variants'] ?? [$item['text']]) }}"
                                    data-label="{{ $label }}">
                                    <td class="fw-medium">
                                                     <a href="#" class="entity-jump-link">{{ $item['text'] }}</a>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill"
                                              style="background:var(--entity-{{ strtolower($label ?? '') }},#ddd);color:#333">
                                            {{ $displayLabel }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $item['count'] }}</span>
                                    </td>
                                    <td>
                                        <input type="text"
                                               class="form-control form-control-sm entity-label-input"
                                               value="[{{ $displayLabel }} {{ $correlative }}]"
                                               placeholder="[ETIQUETA]"
                                               style="min-width:145px;font-size:.8rem;">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 text-muted" style="font-size:.75rem;">
                    <i class="fas fa-mouse-pointer me-1"></i>Clic derecho sobre una entidad en el texto para ignorarla o agregarla a la blacklist
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-primary btn-sm" id="btnAnonimizar">Anonimizar</button>
                </div>
            </div>
        </div>
        @endif

    </div>{{-- /col-lg-9 --}}

</div>{{-- /row --}}

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    // ── Entity type switches: Select All / Select None ───────────────────
    const entityCheckboxes = document.querySelectorAll('.entity-type-checkbox');

    function getSelectedEntityTypes() {
        return Array.from(entityCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.dataset.entityType);
    }

    function syncEntityFilterInputs() {
        const selected = getSelectedEntityTypes();
        const val = selected.join(',');
        const filterInput = document.getElementById('entityFilterInput');
        const uploadFilterInput = document.getElementById('uploadEntityFilterInput');
        if (filterInput) filterInput.value = val;
        if (uploadFilterInput) uploadFilterInput.value = val;
    }

    // Update label styling when toggling
    entityCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            const label = document.querySelector(`.entity-type-label[data-entity-type="${cb.dataset.entityType}"]`);
            if (label) {
                label.classList.toggle('disabled', !cb.checked);
            }
            syncEntityFilterInputs();
        });
    });

    // Select All / None buttons
    document.getElementById('btnSelectAll')?.addEventListener('click', (e) => {
        e.preventDefault();
        entityCheckboxes.forEach(cb => {
            cb.checked = true;
            const label = document.querySelector(`.entity-type-label[data-entity-type="${cb.dataset.entityType}"]`);
            if (label) label.classList.remove('disabled');
        });
        syncEntityFilterInputs();
    });

    document.getElementById('btnSelectNone')?.addEventListener('click', (e) => {
        e.preventDefault();
        entityCheckboxes.forEach(cb => {
            cb.checked = false;
            const label = document.querySelector(`.entity-type-label[data-entity-type="${cb.dataset.entityType}"]`);
            if (label) label.classList.add('disabled');
        });
        syncEntityFilterInputs();
    });

    // Initialize filter inputs — restore from sessionStorage
    const savedFilters = sessionStorage.getItem('alfa-entity-filters');
    if (savedFilters) {
        try {
            const saved = JSON.parse(savedFilters);
            entityCheckboxes.forEach(cb => {
                const type = cb.dataset.entityType;
                if (type in saved) {
                    cb.checked = saved[type];
                    const label = document.querySelector(`.entity-type-label[data-entity-type="${type}"]`);
                    if (label) label.classList.toggle('disabled', !cb.checked);
                }
            });
        } catch(e) {}
    }
    syncEntityFilterInputs();

    // Save entity filter state to sessionStorage on every change
    function saveEntityFiltersToSession() {
        const state = {};
        entityCheckboxes.forEach(cb => { state[cb.dataset.entityType] = cb.checked; });
        sessionStorage.setItem('alfa-entity-filters', JSON.stringify(state));
    }
    entityCheckboxes.forEach(cb => cb.addEventListener('change', saveEntityFiltersToSession));

    // ── Loader en el botón Analizar (PDF) ────────────────────────────────
    const uploadForm = document.getElementById('uploadForm');
    const btnAnalizar = document.getElementById('btnAnalizar');
    if (uploadForm) {
        uploadForm.addEventListener('submit', (e) => {
            const selected = getSelectedEntityTypes();
            if (selected.length === 0) {
                e.preventDefault();
                showToast('Seleccione al menos un tipo de entidad para analizar.', 'warning');
                return;
            }
            syncEntityFilterInputs();
            if (btnAnalizar) {
                btnAnalizar.disabled = true;
                btnAnalizar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando…';
            }
        });
    }

    // ── Estado vacío del editor (placeholder) ────────────────────────────
    const editorMain = document.getElementById('editor-container');
    function syncEditorEmpty() {
        if (!editorMain) return;
        const empty = editorMain.innerText.trim() === '';
        editorMain.setAttribute('data-empty', empty ? 'true' : 'false');
    }
    if (editorMain) {
        editorMain.addEventListener('input', syncEditorEmpty);
        editorMain.addEventListener('paste', () => setTimeout(syncEditorEmpty, 0));
        syncEditorEmpty(); // init on load
    }

    // ── Variables y funciones compartidas ─────────────────────────────────
    let activeMenu = null;

    function closeActiveMenu() {
        if (!activeMenu) return;
        activeMenu.remove();
        activeMenu = null;
    }

    function removeEntityRowFromTable(entityText, entityLabel) {
        const badge = document.getElementById('entity-count-badge');
        document.querySelectorAll('.entity-row').forEach(row => {
            let variants = [];
            try { variants = JSON.parse(row.dataset.entityTexts || '[]').map(v => (v || '').trim()); } catch (e) {}
            const label = row.dataset.label || '';
            if (variants.includes(entityText.trim()) && label === entityLabel) {
                row.remove();
                if (badge) {
                    const n = parseInt(badge.textContent) || 1;
                    badge.textContent = Math.max(0, n - 1) + ' entidades detectadas';
                }
            }
        });
        // Eliminar filas de separador que quedaron sin entidades debajo
        document.querySelectorAll('.table-secondary').forEach(sep => {
            let next = sep.nextElementSibling;
            let hasEntityRow = false;
            while (next && !next.classList.contains('table-secondary')) {
                if (next.classList.contains('entity-row')) { hasEntityRow = true; break; }
                next = next.nextElementSibling;
            }
            if (!hasEntityRow) sep.remove();
        });
    }

    function positionMenu(menu, anchor) {
        const mh = menu.offsetHeight || 120;
        const rect = anchor.getBoundingClientRect();
        const spaceAbove = rect.top;
        const spaceBelow = window.innerHeight - rect.bottom;
        if (spaceAbove > mh + 10 || spaceAbove >= spaceBelow) {
            menu.style.bottom = 'calc(100% + 5px)';
            menu.style.top    = 'auto';
            menu.setAttribute('data-dir', 'up');
        } else {
            menu.style.top    = 'calc(100% + 5px)';
            menu.style.bottom = 'auto';
            menu.setAttribute('data-dir', 'down');
        }
    }

    // ── Botón Analizar texto (texto pegado en el editor) ──────────────────
    const btnAnalizeText = document.getElementById('btnAnalizeText');
    if (btnAnalizeText) {
        btnAnalizeText.addEventListener('click', () => {
            const editorEl = document.getElementById('editor-container');
            const form     = document.getElementById('analyzeTextForm');
            const input    = document.getElementById('analyzeTextInput');
            if (!editorEl || !form || !input) return;

            const text = editorEl.innerText.trim();
            if (!text || text.length < 10) {
                showToast('Ingrese al menos 10 caracteres de texto para analizar.', 'warning');
                return;
            }

            // Check at least one entity type is selected
            const selected = getSelectedEntityTypes();
            if (selected.length === 0) {
                showToast('Seleccione al menos un tipo de entidad para analizar.', 'warning');
                return;
            }

            syncEntityFilterInputs();
            input.value = text;
            btnAnalizeText.disabled = true;
            btnAnalizeText.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Analizando…';
            form.submit();
        });
    }

    // Show entity menu on right-click (context menu) instead of left click
    document.addEventListener('contextmenu', (e) => {
        closeActiveMenu();

        // ── Caso A: clic derecho sobre texto seleccionado (no sobre un span de entidad) ──
        const selection = window.getSelection();
        const selectedText = selection ? selection.toString().trim() : '';
        const span = e.target.closest('.entity');
        const editor = document.getElementById('editor-container');
        const inEditor = editor && editor.contains(e.target);

        if (selectedText && !span && inEditor) {
            e.preventDefault();

            // Crear un menú flotante anclado en el punto de clic
            const menu = document.createElement('div');
            menu.className = 'entity-menu';
            menu.style.position = 'fixed';
            menu.style.left = Math.min(e.clientX, window.innerWidth - 260) + 'px';
            // Posicionar encima o debajo del cursor segun espacio
            const spaceBelow = window.innerHeight - e.clientY;
            if (spaceBelow < 100) {
                menu.style.top    = 'auto';
                menu.style.bottom = (window.innerHeight - e.clientY + 4) + 'px';
            } else {
                menu.style.top  = (e.clientY + 8) + 'px';
                menu.style.bottom = 'auto';
            }
            menu.style.zIndex    = '99999';
            menu.style.transform = 'none';
            menu.innerHTML = `
                <div style="padding:5px 14px 3px;font-size:.75rem;color:#888;font-weight:600;letter-spacing:.04em;">TEXTO SELECCIONADO</div>
                <div style="padding:2px 14px 4px;font-size:.8rem;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:240px;">"${selectedText.length > 40 ? selectedText.slice(0,40) + '…' : selectedText}"</div>
                <hr style="margin:3px 0">
                <button data-action="sel-blacklist">🗃️ Agregar a la Blacklist (omitir)</button>
                <button data-action="sel-whitelist">✅ Agregar a la Whitelist (reconocer)</button>
            `;
            document.body.appendChild(menu);
            menu.style.display = 'block';
            activeMenu = menu;

            menu.querySelector('[data-action="sel-blacklist"]').addEventListener('click', async (ev) => {
                ev.stopPropagation();
                const btn = ev.currentTarget;
                btn.classList.add('loading'); btn.textContent = '⏳ Guardando…';
                try {
                    const res = await fetch("{{ route('pdf-analyzer.add-blacklist') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ term: selectedText, entity_type: null }),
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.message || 'Error');
                    showToast(data.message, 'success');
                } catch (err) { showToast('❌ ' + err.message, 'danger'); }
                finally { closeActiveMenu(); }
            }, { once: true });

            menu.querySelector('[data-action="sel-whitelist"]').addEventListener('click', async (ev) => {
                ev.stopPropagation();
                const btn = ev.currentTarget;
                btn.classList.add('loading'); btn.textContent = '⏳ Guardando…';
                try {
                    const res = await fetch("{{ route('pdf-analyzer.add-whitelist') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ term: selectedText, entity_type: null }),
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.message || 'Error');
                    showToast(data.message, 'success');
                } catch (err) { showToast('❌ ' + err.message, 'danger'); }
                finally { closeActiveMenu(); }
            }, { once: true });

            e.stopPropagation();
            return;
        }

        // ── Caso B: clic derecho sobre un span de entidad ─────────────────────
        if (!span) return;
        e.preventDefault();

        // Obtener texto y tipo de entidad desde el span
        const entityText  = getSpanOwnText(span).trim();
        const entityLabel = span.dataset.label || ''; // ej: PER, ORG, LOC, DNI...

        // Crear menú
        let menu = span.querySelector('.entity-menu');
        if (menu) menu.remove();

        menu = document.createElement('div');
        menu.className = 'entity-menu';
        menu.innerHTML = `
            <button data-action="ignore-once">🚫 Ignorar entidad sólo esta vez</button>
            <hr>
            <button data-action="add-blacklist">🗃️ Ignorar y agregar a la blacklist</button>
        `;
        span.style.position = 'relative';
        span.appendChild(menu);

        menu.style.display = 'block';
        activeMenu = menu;

        // Posicionamiento inteligente (encima/debajo según espacio disponible)
        requestAnimationFrame(() => positionMenu(menu, span));

        e.stopPropagation();

        // ── Acción: Ignorar sólo esta vez ────────────────────────────────────
        // Reemplaza el span por texto plano Y elimina fila en la tabla.
        menu.querySelector('[data-action="ignore-once"]').addEventListener('click', (ev) => {
            ev.stopPropagation();
            // Eliminar TODOS los spans de esta entidad en el editor
            const editorEl = document.getElementById('editor-container');
            if (editorEl) {
                editorEl.querySelectorAll('.entity').forEach(s => {
                    if (getSpanOwnText(s).trim() === entityText &&
                        (s.dataset.label || '') === entityLabel) {
                        s.replaceWith(document.createTextNode(getSpanOwnText(s)));
                    }
                });
            }
            removeEntityRowFromTable(entityText, entityLabel);
            closeActiveMenu();
            showToast('Entidad ignorada en este análisis.', 'info');
        }, { once: true });

        // ── Acción: Ignorar y agregar a la blacklist ──────────────────────────
        // Llama al backend via AJAX para guardar en entity_blacklist (PostgreSQL),
        // y luego elimina TODAS las ocurrencias de esta entidad en el editor.
        menu.querySelector('[data-action="add-blacklist"]').addEventListener('click', async (ev) => {
            ev.stopPropagation();

            const btn = menu.querySelector('[data-action="add-blacklist"]');
            btn.classList.add('loading');
            btn.textContent = '⏳ Guardando…';

            try {
                // 1. Enviar POST al backend para guardar en la blacklist
                const response = await fetch("{{ route('pdf-analyzer.add-blacklist') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        term:        entityText,
                        entity_type: entityLabel || null,
                    }),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Error al guardar en la lista negra.');
                }

                // 2. Eliminar TODAS las ocurrencias de esta entidad en el editor
                //    para que queden como texto plano (sin resaltado).
                const editor = document.getElementById('editor-container');
                if (editor) {
                    // Buscar todos los spans con el mismo texto y label
                    editor.querySelectorAll('.entity').forEach(s => {
                        if (getSpanOwnText(s).trim() === entityText &&
                            (s.dataset.label || '') === entityLabel) {
                            s.replaceWith(document.createTextNode(getSpanOwnText(s)));
                        }
                    });
                }

                // 3. Eliminar la fila de la tabla de entidades + actualizar badge
                removeEntityRowFromTable(entityText, entityLabel);

                // 4. Mostrar confirmación breve al usuario
                showToast(data.message, 'success');

            } catch (err) {
                showToast('❌ ' + err.message, 'danger');
            } finally {
                closeActiveMenu();
            }
        }, { once: true });
    });

    // Cerrar menú contextual al hacer clic izquierdo en cualquier parte
    // (no interfiere con handlers que llaman e.stopPropagation()).
    document.addEventListener('click', (e) => {
        try {
            // Solo si es clic izquierdo (0)
            if (e.button !== 0) return;
            // Si no hay menú activo, nada que hacer
            if (!activeMenu) return;
            // Si el clic ocurrió dentro del propio menú, dejar que el handler lo procese
            if (activeMenu.contains(e.target)) return;
            closeActiveMenu();
        } catch (err) {
            // Silenciar errores menores
        }
    });

    // ── Anonimización: reemplazar spans por etiquetas editables de la tabla ──
    const btnAnonimizar = document.getElementById('btnAnonimizar');
    if (btnAnonimizar) {
        btnAnonimizar.addEventListener('click', async () => {
            const editorEl = document.getElementById('editor-container');
            if (!editorEl) return;

            // Disable and show spinner without altering the button label
            btnAnonimizar.disabled = true;
            if (!btnAnonimizar.querySelector('.anon-spinner')) {
                const sp = document.createElement('span');
                sp.className = 'spinner-border spinner-border-sm me-2 anon-spinner';
                btnAnonimizar.prepend(sp);
            }

            try {
                const rows = Array.from(document.querySelectorAll('.entity-row'));
                if (!rows.length) {
                    showToast('No hay entidades para anonimizar.', 'warning');
                    return;
                }

                // Procesar fila por fila, de arriba hacia abajo
                for (const row of rows) {
                    // Visual: marcar fila en proceso
                    const originalBg = row.style.backgroundColor;
                    row.style.backgroundColor = '#fff3cd';

                    const input = row.querySelector('.entity-label-input');
                    const etiqueta = input ? input.value.trim() : '';
                    if (!etiqueta) {
                        // Restaurar y continuar
                        row.style.backgroundColor = originalBg;
                        await new Promise(r => setTimeout(r, 60));
                        continue;
                    }

                    // Obtener variantes desde data-attribute
                    let variants = [];
                    try { variants = JSON.parse(row.dataset.entityTexts || '[]').map(v => (v||'').trim()).filter(Boolean); } catch (e) { variants = []; }
                    if (!variants.length) variants = [ (row.dataset.entityText || '').trim() ].filter(Boolean);

                    // 1) Reemplazar spans que coincidan con las variantes
                    const spans = Array.from(editorEl.querySelectorAll('.entity'));
                    for (const s of spans) {
                        try {
                            const spanText = getSpanOwnText(s).trim();
                            if (variants.includes(spanText)) {
                                s.replaceWith(document.createTextNode(etiqueta));
                            }
                        } catch (e) {}
                    }

                    // 2) Reemplazar ocurrencias en nodos de texto (fuera de spans)
                    const walker = document.createTreeWalker(editorEl, NodeFilter.SHOW_TEXT, null);
                    const toReplace = [];
                    while (walker.nextNode()) {
                        const tn = walker.currentNode;
                        const txt = tn.nodeValue;
                        for (const variant of variants) {
                            if (!variant) continue;
                            let idx = txt.indexOf(variant);
                            if (idx !== -1) {
                                toReplace.push({ node: tn, variant });
                                break; // procesar este textnode solo una vez por iteración
                            }
                        }
                    }

                    for (const item of toReplace) {
                        const tn = item.node;
                        if (!tn.parentNode) continue; // nodo ya eliminado del DOM
                        const variant = item.variant;
                        let txt = tn.nodeValue;
                        // Reemplazar todas las ocurrencias del variant en este text node
                        const parts = txt.split(variant);
                        if (parts.length <= 1) continue;
                        const frag = document.createDocumentFragment();
                        for (let i = 0; i < parts.length; i++) {
                            if (parts[i].length) frag.appendChild(document.createTextNode(parts[i]));
                            if (i !== parts.length - 1) frag.appendChild(document.createTextNode(etiqueta));
                        }
                        tn.parentNode.replaceChild(frag, tn);
                    }

                    // Pequeña pausa para actualizar UI y que el usuario vea el progreso
                    await new Promise(r => setTimeout(r, 80));
                    // Restaurar estilo
                    row.style.backgroundColor = originalBg;
                }

                // 3) Sincronizar el HTML resultante con la sesión (para exportar)
                const updatedHtml = editorEl.innerHTML;
                fetch("{{ route('pdf-analyzer.anonimize') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ html: updatedHtml }),
                }).catch(() => {}); // sincronización en background, sin bloquear UI

                showToast('Anonimización completada. Texto reemplazado según la tabla.', 'success');
            } catch (err) {
                showToast('No se pudo anonimizar: ' + err.message, 'danger');
            } finally {
                const sp2 = btnAnonimizar.querySelector('.anon-spinner');
                if (sp2) sp2.remove();
                btnAnonimizar.disabled = false;
                // Ensure label remains unchanged
                btnAnonimizar.textContent = 'Anonimizar';
            }
        });
    }

    // Export feature removed: export button and form were deleted from the UI

    // Justify feature removed (button and JS handler deleted)

    // ── Tabla de entidades agrupadas: etiquetas automáticas + acciones ────
    const LABEL_MAP = {
        'PER':     'PERSONA',
        'PERSON':  'PERSONA',
        'ORG':     'ORGANIZACIÓN',
        'LOC':     'LUGAR',
        'GPE':     'LUGAR',
        'DATE':    'FECHA',
        'DNI':     'DNI',
        'EMAIL':   'EMAIL',
        'PHONE':   'TELÉFONO',
        'PATENTE': 'PATENTE',
        'MISC':    'OTRO',
    };

    // 1. Asignar etiquetas automáticas secuenciales por tipo
    const labelCounters = {};
    document.querySelectorAll('.entity-row').forEach(row => {
        const rawLabel = row.dataset.label || '';
        const base     = LABEL_MAP[rawLabel] || rawLabel;
        labelCounters[base] = (labelCounters[base] || 0) + 1;
        const input = row.querySelector('.entity-label-input');
        if (input) input.value = `[${base} ${labelCounters[base]}]`;
    });

    // 3. Funciones helper de entidades
    function getSpanOwnText(span) {
        return Array.from(span.childNodes)
            .filter(n => n.nodeType === Node.TEXT_NODE)
            .map(n => n.textContent)
            .join('');
    }

    function findEntitySpans(entityKey) {
        const editor = document.getElementById('editor-container');
        if (!editor) return [];
        const spans = Array.from(editor.querySelectorAll('.entity'));
        if (Array.isArray(entityKey)) {
            const norms = entityKey.map(v => (v || '').trim());
            return spans.filter(s => norms.includes(getSpanOwnText(s).trim()));
        }
        const key = (entityKey || '').trim();
        return spans.filter(s => getSpanOwnText(s).trim() === key);
    }

    function findVariantsForSpan(span) {
        if (!span) return [];
        const text = getSpanOwnText(span).trim();
        const rows = Array.from(document.querySelectorAll('.entity-row'));
        for (const row of rows) {
            const raw = row.dataset.entityTexts;
            if (!raw) continue;
            try {
                const variants = JSON.parse(raw);
                if (variants && variants.map(v => v.trim()).includes(text)) return variants;
            } catch (e) {}
        }
        return [text];
    }

    // 4. Inicializar tooltips Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        if (window.bootstrap?.Tooltip) new bootstrap.Tooltip(el, { trigger: 'hover' });
    });
    function placeCaretAfter(node) {
        try {
            const range = document.createRange();
            const sel = window.getSelection();
            range.setStartAfter(node);
            range.collapse(true);
            sel.removeAllRanges();
            sel.addRange(range);
        } catch (e) {
            // ignore
        }
    }

    // Keep track of the last flashed span so we can revert its style when a new one is flashed
    let lastFlashedSpan = null;

    function clearFlashForEntity(entityText) {
        findEntitySpans(entityText).forEach(s => s.classList.remove('entity-flash'));
    }

    function scrollToTargetSpan(span) {
        const editor = document.getElementById('editor-container');
        if (!editor || !span) return;

        // Use offsetTop which is relative to the editor container and more reliable
        const lineHeight = Math.max(span.offsetHeight || 18, 18);
        const targetScroll = Math.max(0, span.offsetTop - lineHeight);
        // perform smooth scroll and then ensure caret/focus are set after a short delay
        editor.scrollTo({ top: targetScroll, behavior: 'smooth' });

        // ensure caret/focus/place after occur after scrolling starts
        setTimeout(() => {
            editor.focus();
            placeCaretAfter(span);
        }, 120);
    }

    function flashSpan(span, entityText) {
        if (!span) return;
        // revert previous flashed span (different from this one)
        if (lastFlashedSpan && lastFlashedSpan !== span) {
            lastFlashedSpan.classList.remove('entity-flash');
        }
        // also clear any other flashes of the same entity
        clearFlashForEntity(entityText);
        span.classList.add('entity-flash');
        lastFlashedSpan = span;
    }

    function scrollToFirstOccurrence(entityText) {
        const spans = findEntitySpans(entityText);
        if (!spans || spans.length === 0) return null;
        const target = spans[0];
        scrollToTargetSpan(target);
        flashSpan(target, entityText);
        return target;
    }

    // Clicking the plain-text-like link triggers an immediate jump to the first occurrence
    document.querySelectorAll('.entity-jump-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const row = link.closest('.entity-row');
            let variants = [];
            try { variants = JSON.parse(row?.dataset?.entityTexts || '[]'); } catch (err) { variants = [link.textContent.trim()]; }
            const spans = findEntitySpans(variants);
            if (!spans || spans.length === 0) {
                link.classList.add('text-muted');
                setTimeout(() => link.classList.remove('text-muted'), 900);
                return;
            }

            const target = spans[0];
            // Scroll instantly so the element is visible with the occurrence near second line
            const editor = document.getElementById('editor-container');
            if (editor && target) {
                // compute desired scroll top and set immediately
                const lineHeight = Math.max(target.offsetHeight || 18, 18);
                const top = Math.max(0, target.offsetTop - lineHeight);
                editor.scrollTop = top;
                // focus and place caret shortly after
                setTimeout(() => {
                    editor.focus();
                    placeCaretAfter(target);
                }, 10);
                flashSpan(target, variants);
            }
        });
    });

    // 6. Click izquierdo en cualquier entidad -> ir a la siguiente ocurrencia
    const editor = document.getElementById('editor-container');
    if (editor) {
        // Hover: add temporary hover class so user sees which entity is active
        editor.addEventListener('mouseover', (e) => {
            const span = e.target.closest('.entity');
            if (!span) return;
            span.classList.add('entity-hover');
            // compute variants for this span and show occurrence tooltip relative to the group
            const variants = findVariantsForSpan(span);
            const spans = findEntitySpans(variants);
            const idx = spans.indexOf(span);
            if (idx >= 0) {
                // Try to obtain the entity type from the grouped table (preferred)
                let displayLabel = null;
                const text = getSpanOwnText(span).trim();
                document.querySelectorAll('.entity-row').forEach(row => {
                    const raw = row.dataset.entityTexts;
                    if (!raw) return;
                    try {
                        const variantsList = JSON.parse(raw);
                        if (Array.isArray(variantsList) && variantsList.map(v => v.trim()).includes(text)) {
                            const rawLabel = row.dataset.label || '';
                            displayLabel = (LABEL_MAP[rawLabel] || rawLabel) || displayLabel;
                        }
                    } catch (err) {
                        // ignore
                    }
                });

                // Fallback: infer from CSS class names (legacy)
                if (!displayLabel) {
                    const classMap = {
                        'person': 'PERSONA',
                        'org': 'ORGANIZACIÓN',
                        'location': 'LUGAR',
                        'date': 'FECHA',
                        'dni': 'DNI',
                        'email': 'EMAIL',
                        'phone': 'TELÉFONO',
                        'misc': 'OTRO'
                    };
                    for (const k in classMap) {
                        if (span.classList.contains(k)) { displayLabel = classMap[k]; break; }
                    }
                }

                const title = (displayLabel ? displayLabel + ' — ' : '') + (idx + 1) + '/' + spans.length;
                span.setAttribute('title', title);
            }
        });

        editor.addEventListener('mouseout', (e) => {
            const span = e.target.closest('.entity');
            if (!span) return;
            span.classList.remove('entity-hover');
            span.removeAttribute('title');
        });

        editor.addEventListener('click', (e) => {
            const span = e.target.closest('.entity');
            if (!span) return;

            const variants = findVariantsForSpan(span);
            const spans = findEntitySpans(variants);
            const idx = spans.indexOf(span);
            if (idx >= 0 && idx < spans.length - 1) {
                const next = spans[idx + 1];
                scrollToTargetSpan(next);
                flashSpan(next, variants);
            } else if (idx === spans.length - 1) {
                // Last occurrence: brief pulse to indicate end
                span.style.transition = 'box-shadow 120ms';
                span.style.boxShadow = '0 0 30px rgba(255,255,0,0.95)';
                setTimeout(() => span.style.boxShadow = '', 350);
            }

            // Prevent opening context menu on left click
            e.stopPropagation();
        });
    }

    // Tooltip behavior is handled in the hover/click handlers above (group-aware)

    // ── Función utilitaria: Toast de notificación ─────────────────────────
    // Muestra un mensaje flotante breve en la esquina inferior derecha.
    // type: 'success' | 'danger' | 'warning' | 'info'
    function showToast(message, type = 'info') {
        // Contenedor de toasts (crearlo si no existe)
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:10999;display:flex;flex-direction:column;gap:.5rem;';
            document.body.appendChild(container);
        }

        // Crear el toast
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} shadow py-2 px-3 mb-0`;
        toast.style.cssText = 'min-width:260px;max-width:400px;font-size:.87rem;animation:fadeInUp .2s ease;';
        toast.textContent = message;
        container.appendChild(toast);

        // Auto-eliminar después de 4 segundos
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity .3s';
            setTimeout(() => toast.remove(), 320);
        }, 4000);
    }

});
</script>
@endpush
