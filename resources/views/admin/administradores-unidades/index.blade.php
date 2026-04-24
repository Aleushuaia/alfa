@extends('layouts.dashboard')

@section('title', 'Administradores de Unidades — Alfa')
@section('page-title', 'Administradores de Unidades')
@section('breadcrumb', 'Administradores de Unidades')

@push('styles')
<style>
    .au-admin-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: var(--badge-light-bg);
        border: 1px solid var(--badge-light-border);
        color: var(--body-color);
        border-radius: 20px;
        padding: .2rem .6rem;
        font-size: .76rem;
        font-weight: 500;
    }
    .au-admin-badge .btn-remove {
        background: none;
        border: none;
        padding: 0;
        margin: 0;
        cursor: pointer;
        color: #dc3545;
        font-size: .7rem;
        line-height: 1;
        opacity: .7;
        transition: opacity .15s;
    }
    .au-admin-badge .btn-remove:hover { opacity: 1; }
    .au-unidad-row:hover { background: var(--badge-light-bg); }
    #au-toast-wrap {
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

{{-- Toast container --}}
<div id="au-toast-wrap"></div>

<div class="row g-4">

    {{-- ── Card: tabla de unidades ──────────────────────────────────────── --}}
    <div class="col-12">
        <div class="t-card" style="padding:0">

            <div class="d-flex align-items-center justify-content-between px-4 py-3"
                 style="border-bottom:1px solid var(--card-border)">
                <div>
                    <h5 class="mb-0" style="color:var(--heading-color);font-weight:700;font-size:.95rem">
                        <i class="fas fa-user-shield me-2" style="color:var(--accent)"></i>Asignación de administradores por unidad
                    </h5>
                    <p class="small mb-0 mt-1" style="color:var(--muted-color)">
                        Asigná uno o más administradores a cada unidad de trabajo. Tendrán acceso a gestionar los usuarios de esa unidad.
                    </p>
                </div>
                <span class="badge" style="background:var(--accent);font-size:.75rem;padding:.3rem .75rem;border-radius:20px">
                    {{ $unidades->count() }} unidades
                </span>
            </div>

            <div class="table-responsive">
                <table class="table mb-0" style="color:var(--table-color)">
                    <thead>
                        <tr style="background:var(--table-head-bg);color:var(--table-head-color);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">
                            <th class="px-4 py-3" style="width:35%">Unidad de Trabajo</th>
                            <th class="px-4 py-3">Administradores asignados</th>
                            <th class="px-4 py-3" style="width:280px">Asignar administrador</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unidades as $unidad)
                        <tr class="au-unidad-row" data-unidad-id="{{ $unidad->id }}">
                            <td class="px-4 py-3">
                                <div class="fw-semibold" style="color:var(--heading-color)">{{ $unidad->descripcion }}</div>
                                <div class="small" style="color:var(--muted-color)">#{{ $unidad->id }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="admins-wrap d-flex flex-wrap gap-1" id="admins-{{ $unidad->id }}">
                                    @forelse($unidad->administradores as $admin)
                                    <span class="au-admin-badge" data-admin-id="{{ $admin->id }}" data-unidad-id="{{ $unidad->id }}">
                                        <i class="fas fa-user-shield" style="color:var(--accent);font-size:.65rem"></i>
                                        {{ $admin->name }}
                                        <button type="button" class="btn-remove" title="Quitar administrador"
                                                data-user-id="{{ $admin->id }}"
                                                data-unidad-id="{{ $unidad->id }}"
                                                data-user-name="{{ $admin->name }}">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    </span>
                                    @empty
                                    <span class="text-muted small empty-label" style="font-size:.78rem">Sin administradores</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="d-flex gap-1">
                                    <select class="form-select form-select-sm user-select-au"
                                            data-unidad-id="{{ $unidad->id }}"
                                            style="border-color:var(--input-border);background:var(--input-bg);color:var(--input-color);border-radius:8px;font-size:.8rem">
                                        <option value="">— Seleccioná —</option>
                                        @foreach($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button"
                                            class="btn btn-sm btn-primary btn-assign-admin flex-shrink-0"
                                            data-unidad-id="{{ $unidad->id }}"
                                            style="border-radius:8px;background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;white-space:nowrap"
                                            title="Asignar">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const CSRF        = document.querySelector('meta[name="csrf-token"]').content;
    const ATTACH_URL  = @json(route('admin.administradores-unidades.attach'));
    const DETACH_BASE = @json(url('admin/administradores-unidades'));

    function toast(msg, type) {
        const wrap = document.getElementById('au-toast-wrap');
        const el   = document.createElement('div');
        el.className = 'alert alert-' + type + ' alert-dismissible fade show shadow-sm py-2 px-3';
        el.style.cssText = 'font-size:.83rem;border-radius:10px;min-width:240px;max-width:360px';
        el.innerHTML = msg + '<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>';
        wrap.appendChild(el);
        setTimeout(() => { try { bootstrap.Alert.getOrCreateInstance(el).close(); } catch(e){} }, 4000);
    }

    // ── Asignar administrador ───────────────────────────────────────────────
    document.querySelectorAll('.btn-assign-admin').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const unidadId = this.dataset.unidadId;
            const sel      = document.querySelector(`.user-select-au[data-unidad-id="${unidadId}"]`);
            const userId   = sel?.value;
            const userName = sel?.options[sel.selectedIndex]?.text;

            if (!userId) { toast('Seleccioná un usuario primero.', 'warning'); return; }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const res  = await fetch(ATTACH_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ user_id: userId, unidad_id: unidadId }),
                });
                const json = await res.json();
                if (!res.ok) { toast(json.error || 'Error al asignar.', 'danger'); return; }

                const wrap = document.getElementById('admins-' + unidadId);
                // Quitar etiqueta "Sin administradores" si existe
                wrap.querySelector('.empty-label')?.remove();

                // Agregar badge
                const badge = document.createElement('span');
                badge.className   = 'au-admin-badge';
                badge.dataset.adminId  = userId;
                badge.dataset.unidadId = unidadId;
                badge.innerHTML = `
                    <i class="fas fa-user-shield" style="color:var(--accent);font-size:.65rem"></i>
                    ${userName.trim()}
                    <button type="button" class="btn-remove" title="Quitar"
                            data-user-id="${userId}" data-unidad-id="${unidadId}" data-user-name="${userName.trim()}">
                        <i class="fas fa-times-circle"></i>
                    </button>`;
                wrap.appendChild(badge);

                sel.value = '';
                toast(`<strong>${userName.trim()}</strong> asignado como administrador.`, 'success');
            } catch (e) {
                toast('Error de conexión. Intentá nuevamente.', 'danger');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-plus"></i>';
            }
        });
    });

    // ── Quitar administrador (delegado) ────────────────────────────────────
    document.querySelector('.table tbody').addEventListener('click', async function (e) {
        const btn = e.target.closest('.btn-remove');
        if (!btn) return;

        const userId    = btn.dataset.userId;
        const unidadId  = btn.dataset.unidadId;
        const userName  = btn.dataset.userName;

        if (!confirm(`¿Quitar a "${userName}" como administrador de esta unidad?`)) return;

        btn.disabled = true;

        try {
            const res  = await fetch(`${DETACH_BASE}/${unidadId}/${userId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (!res.ok) { toast(json.error || 'Error al quitar.', 'danger'); btn.disabled = false; return; }

            const badge = btn.closest('.au-admin-badge');
            const wrap  = badge.parentElement;
            badge.remove();

            if (!wrap.querySelector('.au-admin-badge')) {
                const empty = document.createElement('span');
                empty.className = 'text-muted small empty-label';
                empty.style.fontSize = '.78rem';
                empty.textContent = 'Sin administradores';
                wrap.appendChild(empty);
            }

            toast(`<strong>${userName}</strong> removido como administrador.`, 'success');
        } catch (e) {
            toast('Error de conexión. Intentá nuevamente.', 'danger');
            btn.disabled = false;
        }
    });
})();
</script>
@endpush
