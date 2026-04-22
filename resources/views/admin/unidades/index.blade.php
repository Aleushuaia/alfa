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

{{-- ── Modal de eliminación ────────────────────────────────────────────────── --}}
<div class="modal fade" id="modalDeleteUnidad" tabindex="-1" aria-labelledby="modalDeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;overflow:hidden">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border)">
                <h5 class="modal-title" id="modalDeleteLabel" style="color:var(--heading-color);font-size:.95rem">
                    <i class="fas fa-trash-alt me-2" style="color:#ef4444"></i>Eliminar Unidad
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-delete-body" style="color:var(--body-color)">
                {{-- Poblado dinámicamente por JS --}}
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--card-border)">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"
                        style="border-radius:8px;min-width:90px">
                    Cancelar
                </button>
                <form id="form-delete-unidad" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" id="btn-confirm-delete" class="btn btn-sm btn-danger"
                            style="border-radius:8px;min-width:90px;font-weight:600">
                        <i class="fas fa-trash-alt me-1"></i>Eliminar
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
    // ── Filas clickeables ───────────────────────────────────────────────────
    document.querySelectorAll('.unidad-row').forEach(function (row) {
        row.addEventListener('click', function () {
            window.location.href = this.dataset.href;
        });
    });

    // ── Modal de eliminación ────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete-unidad');
        if (!btn) return;

        const id        = btn.dataset.id;
        const desc      = btn.dataset.desc;
        const userCount = parseInt(btn.dataset.users, 10);

        const body       = document.getElementById('modal-delete-body');
        const confirmBtn = document.getElementById('btn-confirm-delete');
        const form       = document.getElementById('form-delete-unidad');

        if (userCount > 0) {
            // Bloquear eliminación
            body.innerHTML = `
                <div class="alert alert-warning mb-0" style="border-radius:10px">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No se puede eliminar la unidad <strong>"${escHtml(desc)}"</strong>
                    porque tiene <strong>${userCount} usuario${userCount !== 1 ? 's' : ''} asociado${userCount !== 1 ? 's' : ''}</strong>.
                    <br><br>
                    <span style="font-size:.87rem">Debés quitar todos los usuarios de la unidad antes de eliminarla.</span>
                </div>`;
            confirmBtn.style.display = 'none';
        } else {
            body.innerHTML = `
                <p style="color:var(--body-color)">
                    ¿Estás seguro de que deseás eliminar la unidad <strong>"${escHtml(desc)}"</strong>?
                </p>
                <p class="small mb-0" style="color:var(--muted-color)">
                    <i class="fas fa-info-circle me-1"></i>Esta acción no se puede deshacer.
                </p>`;
            form.action = `/admin/unidades/${id}`;
            confirmBtn.style.display = '';
        }

        new bootstrap.Modal(document.getElementById('modalDeleteUnidad')).show();
    });

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
