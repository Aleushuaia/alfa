@extends('layouts.dashboard')

@section('title', 'Gestionar: ' . ($unidad->descripcion ?? '#' . $unidad->id))
@section('page-title', 'Gestionar Unidad')
@section('breadcrumb', 'Usuarios de la Unidad')

@section('content')

<div class="row g-4">

    {{-- ── Columna izquierda — Info de la unidad ─────────────────────────── --}}
    <div class="col-lg-4">
        <div class="t-card h-100">

            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="fas fa-building-user" style="color:#fff;font-size:.9rem"></i>
                    </div>
                    <h5 class="mb-0" style="color:var(--heading-color);font-weight:700;font-size:1rem">
                        Mi Unidad
                    </h5>
                </div>
            </div>

            <div class="mb-3 p-3" style="background:var(--badge-light-bg);border:1px solid var(--badge-light-border);border-radius:10px">
                <div class="mb-2">
                    <span class="d-block small fw-semibold text-uppercase" style="letter-spacing:.5px;color:var(--muted-color);font-size:.68rem">ID</span>
                    <span style="color:var(--body-color);font-weight:600">#{{ $unidad->id }}</span>
                </div>
                <div>
                    <span class="d-block small fw-semibold text-uppercase" style="letter-spacing:.5px;color:var(--muted-color);font-size:.68rem">Descripción</span>
                    <span style="color:var(--heading-color);font-weight:600;font-size:1rem">{{ $unidad->descripcion }}</span>
                </div>
            </div>

            <div class="mb-4 p-3" style="background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.2);border-radius:10px">
                <span class="d-block small fw-semibold text-uppercase" style="letter-spacing:.5px;color:var(--muted-color);font-size:.68rem">Usuarios asociados</span>
                <span id="user-count-badge" style="color:var(--accent);font-weight:700;font-size:1.4rem">
                    {{ $unidad->users->count() }}
                </span>
            </div>

            <a href="{{ route('gestionar-unidad.index') }}"
               class="btn btn-secondary w-100"
               style="border-radius:10px;font-weight:500">
                <i class="fas fa-arrow-left me-2"></i>Volver al listado
            </a>

        </div>
    </div>

    {{-- ── Columna derecha — Gestión de usuarios ──────────────────────────── --}}
    <div class="col-lg-8">
        <div class="t-card" style="padding:0">

            <div class="d-flex align-items-center justify-content-between px-4 py-3"
                 style="border-bottom:1px solid var(--card-border)">
                <h5 class="mb-0" style="color:var(--heading-color);font-weight:700;font-size:.95rem">
                    <i class="fas fa-users me-2" style="color:var(--accent)"></i>Usuarios asociados
                </h5>
                <span id="users-count-pill" class="badge"
                      style="background:{{ $unidad->users->count() > 0 ? 'var(--accent)' : 'var(--badge-light-bg)' }};
                             color:{{ $unidad->users->count() > 0 ? '#fff' : 'var(--muted-color)' }};
                             font-size:.75rem;padding:.3rem .75rem;border-radius:20px">
                    {{ $unidad->users->count() }}
                </span>
            </div>

            {{-- Formulario agregar usuario --}}
            @if($availableUsers->isNotEmpty())
            <div class="px-4 py-3" style="border-bottom:1px solid var(--card-border);background:var(--badge-light-bg)">
                <label class="form-label fw-semibold small text-uppercase mb-2"
                       style="letter-spacing:.5px;color:var(--muted-color);font-size:.68rem">Agregar usuario</label>

                <div class="input-group mb-2">
                    <span class="input-group-text" style="background:var(--input-bg);border-color:var(--input-border);color:var(--muted-color)">
                        <i class="fas fa-search fa-sm"></i>
                    </span>
                    <input type="text" id="user-search" class="form-control" placeholder="Buscar por nombre o email…" autocomplete="off">
                </div>

                <select id="user-select" class="form-select mb-2" size="4"
                        style="max-height:120px;border-color:var(--input-border);background:var(--input-bg);color:var(--input-color)">
                    <option value="">— Seleccioná un usuario —</option>
                    @foreach($availableUsers as $u)
                    <option value="{{ $u->id }}" data-name="{{ $u->name }}" data-email="{{ $u->email }}">
                        {{ $u->name }} — {{ $u->email }}
                    </option>
                    @endforeach
                </select>

                <button type="button" id="btn-add-user"
                        class="btn btn-primary w-100"
                        style="background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:10px;font-weight:600">
                    <i class="fas fa-user-plus me-2"></i>Agregar usuario
                </button>
            </div>
            @else
                @if($unidad->users->isNotEmpty())
                <div class="px-4 py-3 small" style="color:var(--muted-color);border-bottom:1px solid var(--card-border);background:var(--badge-light-bg)">
                    <i class="fas fa-info-circle me-1"></i>
                    Todos los usuarios ya están asociados a esta unidad.
                </div>
                @endif
            @endif

            {{-- Tabla usuarios --}}
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="color:var(--table-color)">
                    <thead>
                        <tr style="background:var(--table-head-bg);color:var(--table-head-color);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3 text-end" style="width:110px">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="users-tbody">
                        @if($unidad->users->isEmpty())
                        <tr class="empty-row">
                            <td colspan="3" class="text-center py-5" style="color:var(--muted-color)">
                                <i class="fas fa-user-slash fa-2x mb-2 d-block" style="opacity:.3"></i>
                                <span class="small">Sin usuarios asociados a esta unidad.</span>
                            </td>
                        </tr>
                        @else
                        @foreach($unidad->users as $usr)
                        <tr data-user-id="{{ $usr->id }}">
                            <td class="px-4 py-2 fw-semibold">
                                <i class="fas fa-user-circle me-2" style="color:var(--accent);opacity:.6;font-size:.9rem"></i>
                                {{ $usr->name }}
                            </td>
                            <td class="px-4 py-2" style="font-size:.85rem;color:var(--muted-color)">{{ $usr->email }}</td>
                            <td class="px-4 py-2 text-end">
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger btn-remove-user"
                                        data-user-id="{{ $usr->id }}"
                                        data-user-name="{{ e($usr->name) }}"
                                        style="border-radius:7px;font-size:.78rem"
                                        title="Quitar usuario">
                                    <i class="fas fa-user-minus me-1"></i>Quitar
                                </button>
                            </td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

{{-- Modal confirmar quitar --}}
<div class="modal fade" id="modalRemoveUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border)">
                <h5 class="modal-title" style="color:var(--heading-color);font-size:.95rem">
                    <i class="fas fa-user-minus me-2" style="color:#f59e0b"></i>Quitar usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="color:var(--body-color)">
                <p class="mb-0">¿Quitar a <strong id="remove-user-name"></strong> de esta unidad?</p>
                <p class="small mt-1 mb-0" style="color:var(--muted-color)">El usuario no se elimina del sistema.</p>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--card-border)">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius:8px">Cancelar</button>
                <button type="button" id="btn-confirm-remove" class="btn btn-sm btn-warning" style="border-radius:8px;font-weight:600;color:#fff">
                    <i class="fas fa-user-minus me-1"></i>Quitar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const CSRF       = document.querySelector('meta[name="csrf-token"]').content;
    const ATTACH_URL = @json(route('gestionar-unidad.attach-user', $unidad));
    const DETACH_BASE= @json(url('gestionar-unidad/' . $unidad->id . '/users'));

    const searchInput = document.getElementById('user-search');
    const userSelect  = document.getElementById('user-select');
    const btnAdd      = document.getElementById('btn-add-user');
    const tbody       = document.getElementById('users-tbody');
    const countPill   = document.getElementById('users-count-pill');
    const countBadge  = document.getElementById('user-count-badge');

    let pendingRemoveUserId   = null;
    let pendingRemoveUserName = null;
    const modalRemove = new bootstrap.Modal(document.getElementById('modalRemoveUser'));

    function getCount() {
        return tbody.querySelectorAll('tr[data-user-id]').length;
    }

    function updateCount(n) {
        if (countPill) { countPill.textContent = n; countPill.style.background = n > 0 ? 'var(--accent)' : 'var(--badge-light-bg)'; countPill.style.color = n > 0 ? '#fff' : 'var(--muted-color)'; }
        if (countBadge) countBadge.textContent = n;
    }

    function flashMsg(msg, type) {
        const d = document.createElement('div');
        d.className = 'alert alert-' + type + ' alert-dismissible fade show flash-alert mb-3';
        d.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.getElementById('page-content')?.prepend(d);
        setTimeout(() => { try { bootstrap.Alert.getOrCreateInstance(d).close(); } catch(e){} }, 4000);
    }

    // Filtro búsqueda
    if (searchInput && userSelect) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            Array.from(userSelect.options).forEach(opt => {
                if (!opt.value) return;
                opt.hidden = q.length > 0 && !opt.textContent.toLowerCase().includes(q);
            });
            if (userSelect.selectedOptions[0]?.hidden) userSelect.value = '';
        });
    }

    // Agregar usuario
    if (btnAdd) {
        btnAdd.addEventListener('click', async function () {
            const opt = userSelect?.selectedOptions[0];
            if (!opt?.value) { flashMsg('Seleccioná un usuario de la lista primero.', 'danger'); return; }

            const userId = opt.value, userName = opt.dataset.name, userEmail = opt.dataset.email;
            btnAdd.disabled = true;
            btnAdd.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Agregando…';

            try {
                const res = await fetch(ATTACH_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ user_id: userId }),
                });
                const json = await res.json();
                if (!res.ok) { flashMsg(json.error || 'Error al agregar el usuario.', 'danger'); return; }

                // Quitar opción del select
                opt.remove();

                // Quitar fila vacía si existe
                tbody.querySelector('.empty-row')?.remove();

                // Agregar fila nueva
                const tr = document.createElement('tr');
                tr.dataset.userId = userId;
                tr.innerHTML = `
                    <td class="px-4 py-2 fw-semibold">
                        <i class="fas fa-user-circle me-2" style="color:var(--accent);opacity:.6;font-size:.9rem"></i>
                        ${userName}
                    </td>
                    <td class="px-4 py-2" style="font-size:.85rem;color:var(--muted-color)">${userEmail}</td>
                    <td class="px-4 py-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-user"
                                data-user-id="${userId}" data-user-name="${userName}"
                                style="border-radius:7px;font-size:.78rem">
                            <i class="fas fa-user-minus me-1"></i>Quitar
                        </button>
                    </td>`;
                tbody.appendChild(tr);
                updateCount(getCount());
                flashMsg(`Usuario <strong>${userName}</strong> agregado correctamente.`, 'success');

                // Resetear select
                if (userSelect) userSelect.value = '';
                if (searchInput) searchInput.value = '';
            } catch (e) {
                flashMsg('Error de conexión. Intentá nuevamente.', 'danger');
            } finally {
                btnAdd.disabled = false;
                btnAdd.innerHTML = '<i class="fas fa-user-plus me-2"></i>Agregar usuario';
            }
        });
    }

    // Quitar usuario — captura delegada
    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-user');
        if (!btn) return;
        pendingRemoveUserId   = btn.dataset.userId;
        pendingRemoveUserName = btn.dataset.userName;
        document.getElementById('remove-user-name').textContent = pendingRemoveUserName;
        modalRemove.show();
    });

    // Confirmar quitar
    document.getElementById('btn-confirm-remove').addEventListener('click', async function () {
        if (!pendingRemoveUserId) return;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Quitando…';

        try {
            const res = await fetch(`${DETACH_BASE}/${pendingRemoveUserId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (!res.ok) { flashMsg(json.error || 'Error al quitar el usuario.', 'danger'); return; }

            tbody.querySelector(`tr[data-user-id="${pendingRemoveUserId}"]`)?.remove();
            updateCount(getCount());

            if (getCount() === 0) {
                tbody.innerHTML = `<tr class="empty-row"><td colspan="3" class="text-center py-5" style="color:var(--muted-color)">
                    <i class="fas fa-user-slash fa-2x mb-2 d-block" style="opacity:.3"></i>
                    <span class="small">Sin usuarios asociados a esta unidad.</span></td></tr>`;
            }

            modalRemove.hide();
            flashMsg(`Usuario <strong>${pendingRemoveUserName}</strong> removido de la unidad.`, 'success');
        } catch (e) {
            flashMsg('Error de conexión. Intentá nuevamente.', 'danger');
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-user-minus me-1"></i>Quitar';
            pendingRemoveUserId = pendingRemoveUserName = null;
        }
    });
})();
</script>
@endpush
