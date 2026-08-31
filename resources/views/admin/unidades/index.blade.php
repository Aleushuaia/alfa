@extends('layouts.dashboard')

@section('title', 'Unidades de Trabajo')
@section('page-title', 'Unidades de Trabajo')
@section('breadcrumb', 'Unidades')

@section('content')

{{-- ── Cabecera de página ──────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="mb-0" style="font-size:1.25rem;font-weight:700;color:var(--heading-color)">
            <i class="fas fa-sitemap me-2" style="color:var(--accent)"></i>Unidades de Trabajo
        </h2>
        <p class="mb-0 mt-1 small" style="color:var(--muted-color)">
            Gestión de unidades y sus usuarios asignados
        </p>
    </div>
    @if(auth()->user()->hasRole('administrador'))
    <a href="{{ route('admin.unidades.create') }}"
       class="btn btn-primary"
       style="background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:10px;font-weight:600;padding:.5rem 1.2rem">
        <i class="fas fa-plus me-2"></i>Nueva Unidad
    </a>
    @endif
</div>

{{-- ── Tabla de unidades ───────────────────────────────────────────────────── --}}
<div class="t-card" style="padding:0">
    @if($unidades->isEmpty())
        <div class="text-center py-5" style="color:var(--muted-color)">
            <i class="fas fa-sitemap fa-3x mb-3" style="opacity:.3"></i>
            <p class="mb-0 fw-semibold">No hay unidades registradas.</p>
            <p class="small mt-1" style="opacity:.7">Creá la primera unidad de trabajo.</p>
            <a href="{{ route('admin.unidades.create') }}" class="btn btn-sm btn-primary mt-2"
               style="background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:8px">
                <i class="fas fa-plus me-1"></i>Nueva Unidad
            </a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="color:var(--table-color)">
                <thead>
                    <tr style="background:var(--table-head-bg);color:var(--table-head-color);font-size:.75rem;text-transform:uppercase;letter-spacing:.5px">
                        <th class="px-4 py-3" style="width:70px">#</th>
                        <th class="px-4 py-3">Descripción</th>
                        <th class="px-4 py-3 text-center" style="width:120px">Usuarios</th>
                        <th class="px-4 py-3 text-end" style="width:160px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($unidades as $u)
                    <tr class="unidad-row" data-href="{{ route('admin.unidades.show', $u) }}"
                        style="cursor:pointer;transition:background .15s">
                        <td class="px-4 py-3" style="font-size:.8rem;color:var(--muted-color)">#{{ $u->id }}</td>
                        <td class="px-4 py-3 fw-semibold">
                            <i class="fas fa-sitemap me-2" style="color:var(--accent);opacity:.5;font-size:.85rem"></i>
                            {{ $u->descripcion }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="badge rounded-pill"
                                  style="background:{{ $u->users_count > 0 ? 'rgba(59,130,246,.15)' : 'var(--badge-light-bg)' }};
                                         color:{{ $u->users_count > 0 ? 'var(--accent)' : 'var(--muted-color)' }};
                                         font-size:.72rem;font-weight:600;padding:.3rem .7rem">
                                {{ $u->users_count }} {{ $u->users_count === 1 ? 'usuario' : 'usuarios' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-end" onclick="event.stopPropagation()">
                            <a href="{{ route('admin.unidades.show', $u) }}"
                               class="btn btn-sm btn-outline-primary me-1"
                               style="border-radius:7px;font-size:.78rem" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.unidades.edit', $u) }}"
                               class="btn btn-sm btn-outline-secondary me-1"
                               style="border-radius:7px;font-size:.78rem" title="Editar">
                                <i class="fas fa-pen"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger btn-delete-unidad"
                                    data-id="{{ $u->id }}"
                                    data-desc="{{ e($u->descripcion) }}"
                                    data-users="{{ $u->users_count }}"
                                    style="border-radius:7px;font-size:.78rem" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ── Modal de confirmación de eliminación ──────────────────────────────── --}}
<div class="modal fade" id="modalDeleteUnidad" tabindex="-1" aria-labelledby="modalDeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content"
             style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:18px;overflow:hidden">

            <div class="modal-header border-0" style="padding:1.25rem 1.5rem .25rem">
                <h5 class="modal-title" id="modalDeleteLabel"
                    style="color:var(--heading-color);font-size:1rem;font-weight:700">
                    Eliminar unidad de trabajo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body text-center" style="padding:.75rem 1.75rem 1.5rem">
                <div id="modal-delete-icon"
                     class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                     style="width:60px;height:60px;border-radius:50%;background:rgba(239,68,68,.12)">
                    <i class="fas fa-trash-alt" style="font-size:1.4rem;color:#ef4444"></i>
                </div>
                <div id="modal-delete-body" style="color:var(--body-color)"></div>
            </div>

            <div class="modal-footer border-0" style="padding:0 1.5rem 1.5rem;gap:.6rem;flex-wrap:nowrap">
                <button type="button" class="btn flex-fill" data-bs-dismiss="modal"
                        style="border-radius:10px;font-weight:600;border:1px solid var(--card-border);color:var(--body-color)">
                    Cancelar
                </button>
                <form id="form-delete-unidad" method="POST" class="flex-fill m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" id="btn-confirm-delete" class="btn btn-danger w-100"
                            style="border-radius:10px;font-weight:600">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="btn-confirm-spinner"
                              role="status" aria-hidden="true"></span>
                        <i class="fas fa-trash-alt me-1" id="btn-confirm-icon"></i>Sí, eliminar
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    // ── Navegación al hacer clic en la fila ───────────────────────────────
    document.querySelectorAll('.unidad-row').forEach(function (row) {
        row.addEventListener('click', function () {
            window.location.href = this.dataset.href;
        });
    });

    // ── Confirmación de eliminación ───────────────────────────────────────
    const modalEl     = document.getElementById('modalDeleteUnidad');
    const modal       = modalEl ? new bootstrap.Modal(modalEl) : null;
    const bodyEl      = document.getElementById('modal-delete-body');
    const iconEl      = document.getElementById('modal-delete-icon');
    const form        = document.getElementById('form-delete-unidad');
    const confirmBtn  = document.getElementById('btn-confirm-delete');
    const spinner     = document.getElementById('btn-confirm-spinner');
    const confirmIcon = document.getElementById('btn-confirm-icon');
    const baseUrl     = "{{ url('admin/unidades') }}";

    document.querySelectorAll('.btn-delete-unidad').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const id        = this.dataset.id;
            const desc      = this.dataset.desc || '';
            const userCount = parseInt(this.dataset.users, 10) || 0;

            if (userCount > 0) {
                iconEl.style.background = 'rgba(245,158,11,.14)';
                iconEl.innerHTML = '<i class="fas fa-triangle-exclamation" style="font-size:1.4rem;color:#f59e0b"></i>';
                bodyEl.innerHTML =
                    '<p class="fw-bold mb-2" style="font-size:1rem;color:var(--heading-color)">No se puede eliminar esta unidad</p>' +
                    '<p class="mb-0" style="font-size:.9rem">La unidad <strong>&laquo;' + escHtml(desc) + '&raquo;</strong> ' +
                    'tiene <strong>' + userCount + ' usuario' + (userCount !== 1 ? 's' : '') +
                    ' asociado' + (userCount !== 1 ? 's' : '') + '</strong>.<br>' +
                    'Quitá primero todos los usuarios de la unidad para poder eliminarla.</p>';
                confirmBtn.style.display = 'none';
            } else {
                iconEl.style.background = 'rgba(239,68,68,.12)';
                iconEl.innerHTML = '<i class="fas fa-trash-alt" style="font-size:1.4rem;color:#ef4444"></i>';
                bodyEl.innerHTML =
                    '<p class="fw-bold mb-2" style="font-size:1rem;color:var(--heading-color)">¿Eliminar esta unidad?</p>' +
                    '<div class="py-2 px-3 mb-3 d-inline-block" style="background:var(--badge-light-bg);border-radius:10px;font-weight:600;color:var(--body-color)">' +
                    '<i class="fas fa-sitemap me-2" style="color:var(--accent);opacity:.6"></i>' + escHtml(desc) + '</div>' +
                    '<p class="mb-0" style="font-size:.85rem;color:var(--muted-color)">' +
                    '<i class="fas fa-circle-info me-1"></i>Esta acción no se puede deshacer.</p>';
                form.action = baseUrl + '/' + encodeURIComponent(id);
                confirmBtn.style.display = '';
            }

            if (modal) modal.show();
        });
    });

    // ── Estado de carga al confirmar ─────────────────────────────────────
    if (form) {
        form.addEventListener('submit', function () {
            confirmBtn.disabled = true;
            spinner.classList.remove('d-none');
            confirmIcon.classList.add('d-none');
        });
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
})();
</script>
@endpush
