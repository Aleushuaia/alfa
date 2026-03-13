@extends('layouts.dashboard')

@section('title', 'Analizador de PDF — SAE Kayen')
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
    .entity.person   { background: #ffcccc; }
    .entity.org      { background: #cce5ff; }
    .entity.location { background: #ccffcc; }
    .entity.date     { background: #ffe0b3; }
    .entity.dni      { background: #e0e0e0; }
    .entity.email    { background: #ccf2ff; }
    .entity.phone    { background: #ffffcc; }
    .entity.misc     { background: #e0ccff; }

    /* ── Menú flotante al hacer clic ────────────────────── */
    .entity-menu {
        display: none;
        position: absolute;
        z-index: 9999;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,.15);
        padding: 4px 0;
        min-width: 175px;
        top: 100%;
        left: 0;
    }
    .entity-menu button {
        display: block;
        width: 100%;
        padding: 6px 14px;
        background: none;
        border: none;
        text-align: left;
        font-size: .85rem;
        cursor: pointer;
    }
    .entity-menu button:hover { background: #f5f5f5; }

    /* ── Fila deshabilitada en tabla de entidades ────────── */
    .entity-row-disabled td { opacity: .38; }
    .entity-row-disabled input,
    .entity-row-disabled button { pointer-events: none !important; }
    .entity-row-disabled { background: #f8f8f8 !important; }

    /* ── Editor ─────────────────────────────────────────── */
    #editor-container {
        border: 1px solid #dee2e6;
        border-radius: .375rem;
        min-height: 420px;
        max-height: 620px;
        overflow-y: auto;
        padding: 1rem 1.25rem;
        background: #fefefe;
        line-height: 1.8;
        font-size: .95rem;
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

        {{-- Leyenda de colores --}}
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">
                <i class="fas fa-palette me-2"></i>Leyenda
            </div>
            <div class="card-body p-2">
                <table class="table table-sm table-borderless mb-0" style="font-size:.82rem;">
                    <tr><td><span class="legend-dot" style="background:#ffcccc"></span>Persona</td></tr>
                    <tr><td><span class="legend-dot" style="background:#cce5ff"></span>Organización</td></tr>
                    <tr><td><span class="legend-dot" style="background:#ccffcc"></span>Lugar</td></tr>
                    <tr><td><span class="legend-dot" style="background:#ffe0b3"></span>Fecha</td></tr>
                    <tr><td><span class="legend-dot" style="background:#e0e0e0"></span>DNI</td></tr>
                    <tr><td><span class="legend-dot" style="background:#ccf2ff"></span>Email</td></tr>
                    <tr><td><span class="legend-dot" style="background:#ffffcc"></span>Teléfono</td></tr>
                    <tr><td><span class="legend-dot" style="background:#e0ccff"></span>Otros</td></tr>
                </table>
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
                @if(isset($analyzedHtml))
                <div class="d-flex gap-2">
                    <button class="btn btn-warning btn-sm" id="btnAnonimizar">
                        <i class="fas fa-user-secret me-1"></i>Anonimizar sensibles
                    </button>
                    <button class="btn btn-success btn-sm" id="btnExportar">
                        <i class="fas fa-file-export me-1"></i>Exportar PDF anonimizado
                    </button>
                </div>
                @endif
            </div>

            <div class="card-body">
                @if(isset($analyzedHtml))
                    {{-- Editor de texto con entidades --}}
                    <div id="editor-container" contenteditable="true">
                        {!! $analyzedHtml !!}
                    </div>
                    {{-- Formulario oculto para exportar --}}
                    <form method="GET"
                          action="{{ route('pdf-analyzer.export') }}"
                          id="exportForm">
                    </form>
                @else
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-file-upload fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">Sube un PDF para comenzar el análisis.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Estadísticas agrupadas de entidades --}}
        @if(isset($groupedEntities) && count($groupedEntities))
        <div class="card shadow-sm mt-3">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <div><i class="fas fa-list-ul me-2"></i>Entidades detectadas <span class="fw-normal text-muted">(agrupadas)</span></div>
                <span class="badge bg-secondary">{{ count($groupedEntities) }} entidades únicas</span>
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
                                <th class="text-center">Acciones</th>
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

                            @foreach($entities as $item)
                                @php $label = $item['label'] ?? ''; @endphp

                                {{-- Separator row when label/type changes (use Spanish display names) --}}
                                @php $displayLabel = $labelMap[$label] ?? ($label ?: 'OTROS'); @endphp
                                @if($displayLabel !== ($currentLabel ? ($labelMap[$currentLabel] ?? $currentLabel) : $currentLabel))
                                    <tr class="table-secondary">
                                        <td colspan="5" class="fw-semibold">{{ $displayLabel }}</td>
                                    </tr>
                                    @php $currentLabel = $label; @endphp
                                @endif

                                <tr class="entity-row"
                                    data-entity-texts="{{ e(json_encode($item['variants'] ?? [$item['text']])) }}"
                                    data-label="{{ $label }}">
                                    <td class="fw-medium">
                                                     <a href="#" class="entity-jump-link">{{ $item['text'] }}</a>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill"
                                              style="background:var(--entity-{{ strtolower($label ?? '') }},#ddd);color:#333">
                                            {{ $label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $item['count'] }}</span>
                                    </td>
                                    <td>
                                        <input type="text"
                                               class="form-control form-control-sm entity-label-input"
                                               placeholder="[ETIQUETA]"
                                               style="min-width:145px;font-size:.8rem;">
                                    </td>
                                                    <td class="text-center text-nowrap">
                                                        <button class="btn btn-sm btn-outline-secondary btn-ignore-entity"
                                                                title="Ignorar: dejar este texto sin alterar en el documento"
                                                                data-bs-toggle="tooltip">
                                                            <i class="fas fa-eye-slash fa-sm"></i>
                                                        </button>
                                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 text-muted" style="font-size:.75rem;">
                    <i class="fas fa-eye-slash me-1"></i>Ignorar en el texto
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

    // ── Loader en el botón Analizar ───────────────────────────────────────
    const uploadForm = document.getElementById('uploadForm');
    const btnAnalizar = document.getElementById('btnAnalizar');
    if (uploadForm) {
        uploadForm.addEventListener('submit', () => {
            if (btnAnalizar) {
                btnAnalizar.disabled = true;
                btnAnalizar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando…';
            }
        });
    }

    // ── Menú flotante en entidades ────────────────────────────────────────
    let activeMenu = null;

    function closeActiveMenu() {
        if (activeMenu) {
            activeMenu.style.display = 'none';
            activeMenu = null;
        }
    }

    // Show entity menu on right-click (context menu) instead of left click
    document.addEventListener('contextmenu', (e) => {
        const span = e.target.closest('.entity');
        closeActiveMenu();
        if (!span) return;
        e.preventDefault();

        // Crear o reutilizar menú
        let menu = span.querySelector('.entity-menu');
        if (!menu) {
            menu = document.createElement('div');
            menu.className = 'entity-menu';
            menu.innerHTML = `
                <button data-action="ignore">🚫 Ignorar entidad</button>
            `;
            span.style.position = 'relative';
            span.appendChild(menu);
        }

        menu.style.display = 'block';
        activeMenu = menu;
        e.stopPropagation();

        // Acciones del menú (solo Ignorar)
        menu.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', (ev) => {
                ev.stopPropagation();
                const action = btn.dataset.action;
                if (action === 'ignore') {
                    // Replace the entity span with plain text so it remains unaltered in the editor
                    const plain = document.createTextNode(getSpanOwnText(span));
                    span.replaceWith(plain);
                }
                closeActiveMenu();
            }, { once: true });
        });
    });

    // ── Anonimización automática ──────────────────────────────────────────
    const btnAnonimizar = document.getElementById('btnAnonimizar');
    if (btnAnonimizar) {
        btnAnonimizar.addEventListener('click', async () => {
            const editor = document.getElementById('editor-container');
            const currentHtml = editor.innerHTML;

            btnAnonimizar.disabled = true;
            btnAnonimizar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Anonimizando…';

            try {
                const res = await fetch("{{ route('pdf-analyzer.anonimize') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ html: currentHtml }),
                });

                if (!res.ok) throw new Error('Error HTTP ' + res.status);
                const data = await res.json();
                editor.innerHTML = data.html;
            } catch (err) {
                alert('No se pudo anonimizar: ' + err.message);
            } finally {
                btnAnonimizar.disabled = false;
                btnAnonimizar.innerHTML = '<i class="fas fa-user-secret me-1"></i>Anonimizar sensibles';
            }
        });
    }

    // ── Exportar PDF ──────────────────────────────────────────────────────
    const btnExportar = document.getElementById('btnExportar');
    if (btnExportar) {
        btnExportar.addEventListener('click', () => {
            // Antes de exportar, sincroniza el HTML del editor con la sesión
            const editor = document.getElementById('editor-container');
            const currentHtml = editor.innerHTML;

            fetch("{{ route('pdf-analyzer.anonimize') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ html: currentHtml }),
            }).then(() => {
                document.getElementById('exportForm').submit();
            }).catch(() => {
                document.getElementById('exportForm').submit();
            });
        });
    }

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

    // 2. Inicializar tooltips Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        if (window.bootstrap?.Tooltip) new bootstrap.Tooltip(el, { trigger: 'hover' });
    });

    // Helper: texto visible del span (sin hijos como el entity-menu)
    function getSpanOwnText(span) {
        return Array.from(span.childNodes)
            .filter(n => n.nodeType === Node.TEXT_NODE)
            .map(n => n.textContent)
            .join('');
    }

    // Helper: encontrar todos los spans de entidad cuyo texto coincida
    function findEntitySpans(entityKey) {
        const editor = document.getElementById('editor-container');
        if (!editor) return [];
        const spans = Array.from(editor.querySelectorAll('.entity'));

        // If entityKey is an array of variants, match any
        if (Array.isArray(entityKey)) {
            const norms = entityKey.map(v => (v || '').trim());
            return spans.filter(s => norms.includes(getSpanOwnText(s).trim()));
        }

        // otherwise treat as single string
        const key = (entityKey || '').trim();
        return spans.filter(s => getSpanOwnText(s).trim() === key);
    }

    // Helper: given a span, find the grouped row variants that include its visible text
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
            } catch (e) {
                // ignore parse errors
            }
        }
        return [text];
    }

    // Helper: deshabilitar fila visualmente
    function disableEntityRow(row) {
        row.classList.add('entity-row-disabled');
        row.querySelectorAll('input, button').forEach(el => { el.disabled = true; });
    }

    // 3. Botón IGNORAR en la tabla: reemplaza cada ocurrencia con texto plano (no tachado)
    document.querySelectorAll('.btn-ignore-entity').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('.entity-row');
            const raw = row.dataset.entityTexts;
            let variants = [];
            try { variants = JSON.parse(raw); } catch (e) { variants = [row.dataset.entityText]; }
            findEntitySpans(variants).forEach(span => {
                const plain = document.createTextNode(getSpanOwnText(span));
                span.replaceWith(plain);
            });
            disableEntityRow(row);
        });
    });

    // 5. Enlaces de salto: llevar al editor a la primera ocurrencia
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
            if (idx >= 0) span.setAttribute('title', (idx + 1) + '/' + spans.length);
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

});
</script>
@endpush
