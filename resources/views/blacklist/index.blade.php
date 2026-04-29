@extends('layouts.dashboard')

@section('title', 'Gestión de Blacklist — Alfa')
@section('page-title', 'Gestión de Blacklist de Entidades')
@section('breadcrumb', 'Blacklist')

@push('styles')
<style>
    .bl-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: #8898aa;
    }
    .bl-empty i { font-size: 2.5rem; margin-bottom: .75rem; display: block; }
    .badge-type { font-size: .72rem; letter-spacing: .03em; }
    .btn-delete-entry { padding: 2px 8px; font-size: .78rem; line-height: 1.4; }
    tr.removing { opacity: 0; transition: opacity .3s ease; }
    #toast-container-bl {
        position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 10999;
        display: flex; flex-direction: column; gap: .5rem;
    }
    /* Filtros */
    #bl-filter-panel { background: rgba(0,0,0,.025); border-bottom: 1px solid var(--bs-border-color, #dee2e6); }
    .filter-result-info { font-size: .8rem; color: #6c757d; }
    /* Paginación */
    .pg-btn {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 2rem; height: 2rem; padding: 0 .55rem;
        border: 1px solid var(--bs-border-color, #dee2e6); border-radius: .375rem;
        background: #fff; color: var(--bs-body-color, #333); cursor: pointer;
        font-size: .82rem; transition: background .12s, color .12s, border-color .12s;
        text-decoration: none; user-select: none;
    }
    .pg-btn:hover:not([disabled]) { background: var(--bs-danger, #dc3545); color: #fff; border-color: var(--bs-danger, #dc3545); }
    .pg-btn.active { background: var(--bs-danger, #dc3545); color: #fff; border-color: var(--bs-danger, #dc3545); font-weight: 600; }
    .pg-btn[disabled] { opacity: .4; cursor: default; pointer-events: none; }
    .pg-ellipsis { padding: 0 .4rem; line-height: 2rem; color: #6c757d; }
</style>
@endpush

@section('content')

@php
// Labels en español (mismos que EntityConfigController)
$typeLabels = [
    'PER'   => 'Persona',
    'ORG'   => 'Organización',
    'LOC'   => 'Lugar',
    'DATE'  => 'Fecha',
    'DNI'   => 'DNI',
    'EMAIL' => 'Email',
    'PHONE' => 'Teléfono',
    'MISC'  => 'Otro',
];
// Colores configurados por el usuario (vienen del controlador)
// $entityColors es un array ['PER' => '#hex', 'ORG' => '#hex', ...]
// Función de contraste WCAG para elegir texto negro o blanco
if (!function_exists('_ecTextColor')) {
    function _ecTextColor(string $hex): string {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) return '#333';
        $r = hexdec(substr($hex,0,2))/255;
        $g = hexdec(substr($hex,2,2))/255;
        $b = hexdec(substr($hex,4,2))/255;
        $r = $r <= .03928 ? $r/12.92 : (($r+.055)/1.055)**2.4;
        $g = $g <= .03928 ? $g/12.92 : (($g+.055)/1.055)**2.4;
        $b = $b <= .03928 ? $b/12.92 : (($b+.055)/1.055)**2.4;
        $L = .2126*$r + .7152*$g + .0722*$b;
        return $L > .179 ? '#333333' : '#ffffff';
    }
}
@endphp

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-11">

        {{-- Indicador de unidad activa --}}
        @auth
        @php $__ua = app(\App\Services\UnidadActivaService::class)->get(auth()->user()); @endphp
        @if($__ua)
        <div class="d-flex align-items-center gap-2 mb-3" style="font-size:.82rem;color:var(--body-color);opacity:.75">
            <i class="fas fa-sitemap" style="color:var(--accent)"></i>
            <span>Mostrando datos de: <strong>{{ $__ua->descripcion }}</strong></span>
        </div>
        @endif
        @endauth

        <div class="card shadow-sm">

            {{-- Cabecera --}}
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <i class="fas fa-ban me-2 text-danger"></i>
                    <span class="fw-semibold">Entidades en la Blacklist</span>
                    <span class="badge bg-secondary ms-2" id="bl-count">{{ $entries->count() }}</span>
                </div>
                <div class="text-muted" style="font-size:.82rem;">
                    <i class="fas fa-info-circle me-1"></i>
                    Las entidades aquí listadas son ignoradas automáticamente en los análisis de texto.
                </div>
            </div>

            @if($entries->isNotEmpty())
            {{-- Panel de filtros --}}
            <div id="bl-filter-panel" class="px-3 py-2">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-sm-5 col-md-4">
                        <label class="form-label form-label-sm mb-1 fw-medium">
                            <i class="fas fa-search me-1 text-muted"></i>Buscar término
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="bl-filter-text"
                                   placeholder="Empieza con…" autocomplete="off">
                            <button class="btn btn-outline-secondary" id="bl-clear-text" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4 col-md-3">
                        <label class="form-label form-label-sm mb-1 fw-medium">
                            <i class="fas fa-tag me-1 text-muted"></i>Tipo
                        </label>
                        <select class="form-select form-select-sm" id="bl-filter-type">
                            <option value="">Todos los tipos</option>
                            <option value="PER">Persona</option>
                            <option value="ORG">Organización</option>
                            <option value="LOC">Lugar</option>
                            <option value="DATE">Fecha</option>
                            <option value="DNI">DNI</option>
                            <option value="EMAIL">Email</option>
                            <option value="PHONE">Teléfono</option>
                            <option value="MISC">Otro</option>
                            <option value="__none__">Sin tipo</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-3 col-md-2">
                        <label class="form-label form-label-sm mb-1 fw-medium">
                            <i class="fas fa-list-ol me-1 text-muted"></i>Por página
                        </label>
                        <select class="form-select form-select-sm" id="bl-page-size">
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex align-items-end pb-1">
                        <span class="filter-result-info" id="bl-result-info"></span>
                    </div>
                </div>
            </div>
            @endif

            {{-- Tabla --}}
            <div class="card-body p-0">
                @if($entries->isEmpty())
                    <div class="bl-empty">
                        <i class="fas fa-check-circle text-success"></i>
                        <p class="mb-0">La blacklist está vacía. No hay entidades ignoradas.</p>
                    </div>
                @else
                    <div class="table-responsive" id="bl-table-wrap">
                        <table class="table table-hover table-sm align-middle mb-0" id="blacklist-table" style="font-size:.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:38%">Término</th>
                                    <th>Tipo</th>
                                    <th>Modo</th>
                                    <th>Agregado por</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th class="text-center" style="width:70px">Eliminar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entries as $entry)
                                @php
                                    $key  = strtoupper($entry->entity_type ?? '');
                                    $lbl  = $typeLabels[$key] ?? null;
                                    $bg   = $entityColors[$key] ?? null;
                                    $fg   = $bg ? _ecTextColor($bg) : null;
                                @endphp
                                <tr id="bl-row-{{ $entry->id }}"
                                    data-id="{{ $entry->id }}"
                                    data-term="{{ strtolower($entry->term) }}"
                                    data-type="{{ $entry->entity_type ? strtoupper($entry->entity_type) : '__none__' }}">
                                    <td class="fw-medium">{{ $entry->term }}</td>
                                    <td>
                                        @if($lbl && $bg)
                                            <span class="badge badge-type" style="background:{{ $bg }};color:{{ $fg }}">{{ $lbl }}</span>
                                        @else
                                            <span class="text-muted" style="font-size:.8rem;">Todos</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-type bg-light text-dark border">{{ $entry->match_mode ?? 'exact' }}</span>
                                    </td>
                                    <td class="text-muted">{{ $entry->added_by ?? '—' }}</td>
                                    <td class="text-muted" style="white-space:nowrap;">
                                        {{ $entry->created_at ? $entry->created_at->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td>
                                        @if($entry->active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Activa</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-outline-danger btn-delete-entry"
                                                data-id="{{ $entry->id }}"
                                                data-term="{{ $entry->term }}"
                                                title="Eliminar esta entrada de la blacklist">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{-- Sin resultados al filtrar --}}
                    <div id="bl-no-results" class="bl-empty" style="display:none;">
                        <i class="fas fa-search text-muted"></i>
                        <p class="mb-0">Ningún término coincide con los filtros aplicados.</p>
                    </div>
                @endif
            </div>

            @if($entries->isNotEmpty())
            {{-- Footer con paginación --}}
            <div class="card-footer" id="bl-pagination-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-muted" style="font-size:.78rem;">
                        <i class="fas fa-trash-alt me-1"></i>
                        Al eliminar una entrada, la entidad volverá a aparecer en los próximos análisis.
                    </div>
                    <div id="bl-pagination" class="d-flex gap-1 flex-wrap align-items-center"></div>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

<div id="toast-container-bl"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // ── Toast ─────────────────────────────────────────────────────────────────
    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container-bl');
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} shadow py-2 px-3 mb-0`;
        toast.style.cssText = 'min-width:260px;max-width:420px;font-size:.87rem;animation:fadeInUp .2s ease;';
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0'; toast.style.transition = 'opacity .3s';
            setTimeout(() => toast.remove(), 320);
        }, 4500);
    }

    // ── Estado ────────────────────────────────────────────────────────────────
    const tbody = document.querySelector('#blacklist-table tbody');
    if (!tbody) return; // estado vacío inicial

    let allRows     = Array.from(tbody.querySelectorAll('tr[data-id]'));
    let currentPage = 1;
    let pageSize    = 20;

    const filterText  = document.getElementById('bl-filter-text');
    const filterType  = document.getElementById('bl-filter-type');
    const clearText   = document.getElementById('bl-clear-text');
    const pageSizeSel = document.getElementById('bl-page-size');
    const resultInfo  = document.getElementById('bl-result-info');
    const noResults   = document.getElementById('bl-no-results');
    const pgContainer = document.getElementById('bl-pagination');

    // ── Filtrado ──────────────────────────────────────────────────────────────
    function computeFiltered() {
        const txt = (filterText?.value || '').toLowerCase().trim();
        const typ = filterType?.value || '';
        return allRows.filter(row => {
            const term = (row.dataset.term || '');
            const type = (row.dataset.type || '');
            const okTxt = !txt || term.startsWith(txt);
            const okTyp = !typ || type === typ;
            return okTxt && okTyp;
        });
    }

    // ── Render página ─────────────────────────────────────────────────────────
    function renderPage() {
        const filtered    = computeFiltered();
        const total       = filtered.length;
        const totalAll    = allRows.length;
        const totalPages  = Math.max(1, Math.ceil(total / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;

        const start   = (currentPage - 1) * pageSize;
        const visible = filtered.slice(start, start + pageSize);

        allRows.forEach(r => r.style.display = 'none');
        visible.forEach(r => r.style.display = '');

        // Sin resultados
        if (noResults) noResults.style.display = total === 0 ? 'block' : 'none';
        if (document.getElementById('bl-table-wrap'))
            document.getElementById('bl-table-wrap').style.display = total === 0 ? 'none' : '';

        // Contador de resultados
        if (resultInfo) {
            const isFiltered = (filterText?.value || '').trim() || (filterType?.value || '');
            resultInfo.textContent = isFiltered
                ? `${total} de ${totalAll} entrada${totalAll !== 1 ? 's' : ''}`
                : (totalAll > 0 ? `${totalAll} entrada${totalAll !== 1 ? 's' : ''} en total` : '');
        }

        // Badge total
        const badge = document.getElementById('bl-count');
        if (badge) badge.textContent = totalAll;

        renderPagination(totalPages, total);
    }

    // ── Paginación ────────────────────────────────────────────────────────────
    function renderPagination(totalPages, total) {
        if (!pgContainer) return;
        pgContainer.innerHTML = '';
        if (total === 0 || totalPages <= 1) return;

        function makeBtn(label, page, disabled, active) {
            const b = document.createElement('button');
            b.className = 'pg-btn' + (active ? ' active' : '');
            b.disabled  = disabled;
            b.innerHTML = label;
            if (!disabled) b.addEventListener('click', () => { currentPage = page; renderPage(); });
            return b;
        }

        // Siempre: primera, anterior
        pgContainer.appendChild(makeBtn('«', 1,              currentPage === 1, false));
        pgContainer.appendChild(makeBtn('‹', currentPage - 1, currentPage === 1, false));

        // Páginas con elipsis inteligente
        const pages = [];
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            pages.push(1);
            if (currentPage > 3) pages.push('…');
            for (let i = Math.max(2, currentPage - 1); i <= Math.min(totalPages - 1, currentPage + 1); i++) pages.push(i);
            if (currentPage < totalPages - 2) pages.push('…');
            pages.push(totalPages);
        }

        pages.forEach(p => {
            if (p === '…') {
                const s = document.createElement('span');
                s.className = 'pg-ellipsis'; s.textContent = '…';
                pgContainer.appendChild(s);
            } else {
                pgContainer.appendChild(makeBtn(p, p, false, p === currentPage));
            }
        });

        // Siempre: siguiente, última
        pgContainer.appendChild(makeBtn('›', currentPage + 1, currentPage === totalPages, false));
        pgContainer.appendChild(makeBtn('»', totalPages,      currentPage === totalPages, false));
    }

    // ── Manejo del estado vacío total (todos eliminados) ──────────────────────
    function handleAllDeleted() {
        const wrap   = document.getElementById('bl-table-wrap');
        const noRes  = document.getElementById('bl-no-results');
        const filter = document.getElementById('bl-filter-panel');
        const footer = document.getElementById('bl-pagination-footer');
        if (wrap)   wrap.innerHTML = `<div class="bl-empty">
            <i class="fas fa-check-circle text-success" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
            <p class="mb-0">La blacklist está vacía. No hay entidades ignoradas.</p></div>`;
        if (noRes)  noRes.style.display = 'none';
        if (filter) filter.style.display = 'none';
        if (footer) footer.style.display = 'none';
        const badge = document.getElementById('bl-count');
        if (badge) badge.textContent = '0';
    }

    // ── Eventos de filtro ─────────────────────────────────────────────────────
    filterText?.addEventListener('input',  () => { currentPage = 1; renderPage(); });
    filterType?.addEventListener('change', () => { currentPage = 1; renderPage(); });
    clearText?.addEventListener('click',   () => {
        if (filterText) filterText.value = '';
        currentPage = 1; renderPage(); filterText?.focus();
    });
    pageSizeSel?.addEventListener('change', () => {
        pageSize = parseInt(pageSizeSel.value, 10) || 20;
        currentPage = 1; renderPage();
    });

    // ── Render inicial ────────────────────────────────────────────────────────
    renderPage();

    // ── Eliminar (delegado al tbody) ──────────────────────────────────────────
    tbody.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-delete-entry');
        if (!btn) return;

        const id   = btn.dataset.id;
        const term = btn.dataset.term;
        if (!confirm(`¿Eliminar "${term}" de la blacklist?\n\nEsta entidad volverá a aparecer en futuros análisis.`)) return;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const res = await fetch(`/blacklist/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || 'Error al eliminar.');

            const row = document.getElementById(`bl-row-${id}`);
            if (row) {
                row.classList.add('removing');
                setTimeout(() => {
                    row.remove();
                    allRows = allRows.filter(r => r.dataset.id !== id);
                    if (allRows.length === 0) { handleAllDeleted(); return; }
                    renderPage();
                }, 320);
            }
            showToast(data.message, 'success');
        } catch (err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-times"></i>';
            showToast('❌ ' + err.message, 'danger');
        }
    });
});
</script>
@endpush

@push('styles')
<style>
    .bl-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: #8898aa;
    }
    .bl-empty i { font-size: 2.5rem; margin-bottom: .75rem; display: block; }
    .badge-type {
        font-size: .72rem;
        letter-spacing: .03em;
    }
    .btn-delete-entry {
        padding: 2px 8px;
        font-size: .78rem;
        line-height: 1.4;
    }
    tr.removing {
        opacity: 0;
        transition: opacity .3s ease;
    }
    #toast-container-bl {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        z-index: 10999;
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }
</style>
@endpush

@section('content')

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-11">

        {{-- Indicador de unidad activa --}}
        @auth
        @php $__ua = app(\App\Services\UnidadActivaService::class)->get(auth()->user()); @endphp
        @if($__ua)
        <div class="d-flex align-items-center gap-2 mb-3" style="font-size:.82rem;color:var(--body-color);opacity:.75">
            <i class="fas fa-sitemap" style="color:var(--accent)"></i>
            <span>Mostrando datos de: <strong>{{ $__ua->descripcion }}</strong></span>
        </div>
        @endif
        @endauth

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <i class="fas fa-ban me-2 text-danger"></i>
                    <span class="fw-semibold">Entidades en la Blacklist</span>
                    <span class="badge bg-secondary ms-2" id="bl-count">{{ $entries->count() }}</span>
                </div>
                <div class="text-muted" style="font-size:.82rem;">
                    <i class="fas fa-info-circle me-1"></i>
                    Las entidades aquí listadas son ignoradas automáticamente en los análisis de texto.
                </div>
            </div>

            <div class="card-body p-0">
                @if($entries->isEmpty())
                    <div class="bl-empty">
                        <i class="fas fa-check-circle text-success"></i>
                        <p class="mb-0">La blacklist está vacía. No hay entidades ignoradas.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0" id="blacklist-table" style="font-size:.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40%">Término</th>
                                    <th>Tipo</th>
                                    <th>Modo</th>
                                    <th>Agregado por</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th class="text-center" style="width:70px">Eliminar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entries as $entry)
                                <tr id="bl-row-{{ $entry->id }}" data-id="{{ $entry->id }}">
                                    <td class="fw-medium">{{ $entry->term }}</td>
                                    <td>
                                        @if($entry->entity_type)
                                            <span class="badge badge-type bg-secondary">{{ $entry->entity_type }}</span>
                                        @else
                                            <span class="text-muted" style="font-size:.8rem;">Todos</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-type bg-light text-dark border">{{ $entry->match_mode ?? 'exact' }}</span>
                                    </td>
                                    <td class="text-muted">{{ $entry->added_by ?? '—' }}</td>
                                    <td class="text-muted" style="white-space:nowrap;">
                                        {{ $entry->created_at ? $entry->created_at->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td>
                                        @if($entry->active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Activa</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-outline-danger btn-delete-entry"
                                                data-id="{{ $entry->id }}"
                                                data-term="{{ $entry->term }}"
                                                title="Eliminar esta entrada de la blacklist">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if($entries->isNotEmpty())
            <div class="card-footer text-muted" style="font-size:.78rem;">
                <i class="fas fa-trash-alt me-1"></i>
                Al eliminar una entrada, la entidad volverá a aparecer en los próximos análisis de texto.
            </div>
            @endif
        </div>

    </div>
</div>

<div id="toast-container-bl"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container-bl');
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} shadow py-2 px-3 mb-0`;
        toast.style.cssText = 'min-width:260px;max-width:420px;font-size:.87rem;animation:fadeInUp .2s ease;';
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity .3s';
            setTimeout(() => toast.remove(), 320);
        }, 4500);
    }

    function updateCount() {
        const badge = document.getElementById('bl-count');
        if (!badge) return;
        const remaining = document.querySelectorAll('#blacklist-table tbody tr').length;
        badge.textContent = remaining;
        if (remaining === 0) {
            const tbody = document.querySelector('#blacklist-table tbody');
            if (tbody) {
                const colspan = 7;
                tbody.innerHTML = `<tr><td colspan="${colspan}">
                    <div class="bl-empty">
                        <i class="fas fa-check-circle text-success" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                        <p class="mb-0">La blacklist está vacía. No hay entidades ignoradas.</p>
                    </div>
                </td></tr>`;
            }
        }
    }

    document.querySelectorAll('.btn-delete-entry').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id   = btn.dataset.id;
            const term = btn.dataset.term;

            if (!confirm(`¿Eliminar "${term}" de la blacklist?\n\nEsta entidad volverá a aparecer en futuros análisis.`)) return;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                const res = await fetch(`/blacklist/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (!res.ok || !data.success) throw new Error(data.message || 'Error al eliminar.');

                // Fade out and remove the row
                const row = document.getElementById(`bl-row-${id}`);
                if (row) {
                    row.classList.add('removing');
                    setTimeout(() => { row.remove(); updateCount(); }, 320);
                }

                showToast(data.message, 'success');

            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-times"></i>';
                showToast('❌ ' + err.message, 'danger');
            }
        });
    });
});
</script>
@endpush
