@extends('layouts.dashboard')

@section('title', 'Unidad: ' . ($unidad->descripcion ?? '#' . $unidad->id))
@section('page-title', 'Unidades de Trabajo')
@section('breadcrumb', 'Detalle de Unidad')

@section('content')

<div class="row g-4">

    {{-- ══════════════════════════════════════════════════════════════════════
         COLUMNA IZQUIERDA — Detalle de la unidad
         ══════════════════════════════════════════════════════════════════════ --}}
    <div class="col-lg-4">
        <div class="t-card h-100">

            {{-- Cabecera --}}
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="fas fa-sitemap" style="color:#fff;font-size:.9rem"></i>
                    </div>
                    <h5 class="mb-0" style="color:var(--heading-color);font-weight:700;font-size:1rem">
                        Unidad de Trabajo
                    </h5>
                </div>
                <p class="small mb-0" style="color:var(--muted-color)">
                    Información y acciones disponibles
                </p>
            </div>

            {{-- Datos --}}
            <div class="mb-3 p-3" style="background:var(--badge-light-bg);border:1px solid var(--badge-light-border);border-radius:10px">
                <div class="mb-2">
                    <span class="d-block small fw-semibold text-uppercase" style="letter-spacing:.5px;color:var(--muted-color);font-size:.68rem">ID</span>
                    <span style="color:var(--body-color);font-weight:600">#{{ $unidad->id }}</span>
                </div>
                <div>
                    <span class="d-block small fw-semibold text-uppercase" style="letter-spacing:.5px;color:var(--muted-color);font-size:.68rem">Descripción</span>
                    <span style="color:var(--heading-color);font-weight:600;font-size:1rem">
                        {{ $unidad->descripcion }}
                    </span>
                </div>
            </div>

            {{-- Usuarios conteo --}}
            <div class="mb-4 p-3" style="background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.2);border-radius:10px">
                <span class="d-block small fw-semibold text-uppercase" style="letter-spacing:.5px;color:var(--muted-color);font-size:.68rem">Usuarios asociados</span>
                <span id="user-count-badge" style="color:var(--accent);font-weight:700;font-size:1.4rem">
                    {{ $unidad->users->count() }}
                </span>
            </div>

            {{-- Acciones --}}
            <div class="d-flex flex-column gap-2">
                <a href="{{ route('admin.unidades.edit', $unidad) }}"
                   class="btn btn-primary w-100"
                   style="background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:10px;font-weight:600">
                    <i class="fas fa-pen me-2"></i>Editar unidad
                </a>
                <a href="{{ route('admin.unidades.index') }}"
                   class="btn btn-secondary w-100"
                   style="border-radius:10px;font-weight:500">
                    <i class="fas fa-arrow-left me-2"></i>Volver al listado
                </a>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         COLUMNA DERECHA — Usuarios asociados
         ══════════════════════════════════════════════════════════════════════ --}}
    <div class="col-lg-8">
        <div class="t-card" style="padding:0">

            {{-- Cabecera del panel --}}
            <div class="d-flex align-items-center justify-content-between px-4 py-3"
                 style="border-bottom:1px solid var(--card-border)">
                <div>
                    <h5 class="mb-0" style="color:var(--heading-color);font-weight:700;font-size:.95rem">
                        <i class="fas fa-users me-2" style="color:var(--accent)"></i>Usuarios asociados a la unidad
                    </h5>
                </div>
                <span id="users-count-pill" class="badge"
                      style="background:{{ $unidad->users->count() > 0 ? 'var(--accent)' : 'var(--badge-light-bg)' }};
                             color:{{ $unidad->users->count() > 0 ? '#fff' : 'var(--muted-color)' }};
                             font-size:.75rem;padding:.3rem .75rem;border-radius:20px">
                    {{ $unidad->users->count() }}
                </span>
            </div>

            {{-- Formulario para agregar usuarios --}}
            @if($availableUsers->isNotEmpty())
            <div class="px-4 py-3" style="border-bottom:1px solid var(--card-border);background:var(--badge-light-bg)">
                <label class="form-label fw-semibold small text-uppercase mb-2"
                       style="letter-spacing:.5px;color:var(--muted-color);font-size:.68rem">
                    Agregar usuario
                </label>

                {{-- Búsqueda --}}
                <div class="input-group mb-2">
                    <span class="input-group-text"
                          style="background:var(--input-bg);border-color:var(--input-border);color:var(--muted-color)">
                        <i class="fas fa-search fa-sm"></i>
                    </span>
                    <input type="text"
                           id="user-search"
                           class="form-control"
                           placeholder="Buscar por nombre o email…"
                           autocomplete="off">
                </div>

                {{-- Select --}}
                <select id="user-select" class="form-select mb-2" size="4"
                        style="max-height:120px;border-color:var(--input-border);background:var(--input-bg);color:var(--input-color)">
                    <option value="">— Seleccioná un usuario —</option>
                    @foreach($availableUsers as $u)
                    <option value="{{ $u->id }}" data-name="{{ $u->name }}" data-email="{{ $u->email }}">
                        {{ $u->name }} — {{ $u->email }}
                    </option>
                    @endforeach
                </select>

                {{-- Botón agregar --}}
                <button type="button" id="btn-add-user"
                        class="btn btn-primary w-100"
                        style="background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:10px;font-weight:600">
                    <i class="fas fa-user-plus me-2"></i>Agregar usuario
                </button>
            </div>
            @else
                @if($unidad->users->isEmpty())
                {{-- Sin usuarios disponibles ni asociados --}}
                @else
                <div class="px-4 py-3 small" style="color:var(--muted-color);border-bottom:1px solid var(--card-border);background:var(--badge-light-bg)">
                    <i class="fas fa-info-circle me-1"></i>
                    Todos los usuarios del sistema ya están asociados a esta unidad.
                </div>
                @endif
            @endif

            {{-- Tabla de usuarios asociados --}}
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
                            <td class="px-4 py-2" style="font-size:.85rem;color:var(--muted-color)">
                                {{ $usr->email }}
                            </td>
                            <td class="px-4 py-2 text-end">
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger btn-remove-user"
                                        data-user-id="{{ $usr->id }}"
                                        data-user-name="{{ e($usr->name) }}"
                                        data-user-email="{{ e($usr->email) }}"
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

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL — Confirmar quitar usuario
     ══════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalRemoveUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content"
             style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;overflow:hidden">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border)">
                <h5 class="modal-title" style="color:var(--heading-color);font-size:.95rem">
                    <i class="fas fa-user-minus me-2" style="color:#f59e0b"></i>Quitar usuario de la unidad
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="color:var(--body-color)">
                <p class="mb-1">
                    ¿Estás seguro de que deseás quitar a
                    <strong id="remove-user-name"></strong>
                    de esta unidad?
                </p>
                <p class="small mb-0" style="color:var(--muted-color)">
                    <i class="fas fa-info-circle me-1"></i>
                    El usuario no será eliminado del sistema, solo se le quitará la asociación.
                </p>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--card-border)">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"
                        style="border-radius:8px;min-width:90px">
                    Cancelar
                </button>
                <button type="button" id="btn-confirm-remove" class="btn btn-sm btn-warning"
                        style="border-radius:8px;min-width:90px;font-weight:600;color:#fff">
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
    const CSRF     = document.querySelector('meta[name="csrf-token"]').content;
    const ATTACH   = @json(route('admin.unidades.attach-user', $unidad));
    const DETACH_BASE = @json(url('/admin/unidades/' . $unidad->id . '/users'));

    const searchInput  = document.getElementById('user-search');
    const userSelect   = document.getElementById('user-select');
    const btnAdd       = document.getElementById('btn-add-user');
    const tbody        = document.getElementById('users-tbody');
    const countPill    = document.getElementById('users-count-pill');
    const countBadge   = document.getElementById('user-count-badge');

    // ── Filtro de búsqueda en select ────────────────────────────────────────
    if (searchInput && userSelect) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            Array.from(userSelect.options).forEach(function (opt) {
                if (!opt.value) return; // conservar placeholder
                const text = opt.textContent.toLowerCase();
                opt.hidden = q.length > 0 && !text.includes(q);
            });
            // Si la opción seleccionada queda oculta, deseleccionar
            if (userSelect.selectedOptions[0]?.hidden) {
                userSelect.value = '';
            }
        });
    }

    // ── Agregar usuario (AJAX) ──────────────────────────────────────────────
    if (btnAdd) {
        btnAdd.addEventListener('click', async function () {
            const selectedOpt = userSelect.selectedOptions[0];
            if (!selectedOpt?.value) {
                flashMsg('Seleccioná un usuario de la lista primero.', 'danger');
                return;
            }

            const userId    = selectedOpt.value;
            const userName  = selectedOpt.dataset.name;
            const userEmail = selectedOpt.dataset.email;

            btnAdd.disabled = true;
            btnAdd.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Agregando…';

            try {
                const resp = await fetch(ATTACH, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({ user_id: userId }),
                });

                const data = await resp.json();

                if (!resp.ok) {
                    flashMsg(data.error || 'No se pudo agregar el usuario.', 'danger');
                    return;
                }

                // Agregar fila a la tabla
                addRow(data.user.id, data.user.name, data.user.email);

                // Quitar opción del select
                selectedOpt.remove();
                userSelect.value = '';
                if (searchInput) searchInput.value = '';

                // Actualizar contadores
                updateCount(1);

                // Ocultar select si no quedan opciones disponibles
                syncSelectVisibility();

                flashMsg(`Usuario <strong>${escHtml(data.user.name)}</strong> agregado correctamente.`, 'success');

            } catch (err) {
                flashMsg('Error de conexión. Intentá nuevamente.', 'danger');
            } finally {
                btnAdd.disabled = false;
                btnAdd.innerHTML = '<i class="fas fa-user-plus me-2"></i>Agregar usuario';
            }
        });
    }

    // ── Quitar usuario — abrir modal ────────────────────────────────────────
    let pendingUserId = null;
    let pendingRow    = null;

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-user');
        if (!btn) return;

        pendingUserId = btn.dataset.userId;
        pendingRow    = btn.closest('tr');

        document.getElementById('remove-user-name').textContent = btn.dataset.userName;
        new bootstrap.Modal(document.getElementById('modalRemoveUser')).show();
    });

    // ── Quitar usuario — confirmar ──────────────────────────────────────────
    document.getElementById('btn-confirm-remove').addEventListener('click', async function () {
        if (!pendingUserId || !pendingRow) return;

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Quitando…';

        // Recuperar datos del row antes de borrarlo
        const nameEl  = pendingRow.querySelector('td:first-child');
        const emailEl = pendingRow.querySelector('td:nth-child(2)');
        const userName  = nameEl  ? nameEl.textContent.trim().replace(/^\s*\S+\s*/, '') : '';
        const userEmail = emailEl ? emailEl.textContent.trim() : '';

        try {
            const resp = await fetch(`${DETACH_BASE}/${pendingUserId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept':       'application/json',
                },
            });

            if (resp.ok) {
                // Agregar de vuelta al select
                if (userSelect) {
                    const opt = document.createElement('option');
                    opt.value             = pendingUserId;
                    opt.dataset.name      = userName;
                    opt.dataset.email     = userEmail;
                    opt.textContent       = `${userName} — ${userEmail}`;
                    userSelect.appendChild(opt);
                    syncSelectVisibility();
                }

                // Eliminar fila
                pendingRow.remove();
                updateCount(-1);
                checkEmpty();

                flashMsg('Usuario removido de la unidad correctamente.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalRemoveUser'))?.hide();
            } else {
                flashMsg('No se pudo quitar el usuario. Intentá nuevamente.', 'danger');
            }
        } catch (err) {
            flashMsg('Error de conexión. Intentá nuevamente.', 'danger');
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-user-minus me-1"></i>Quitar';
            pendingUserId = null;
            pendingRow    = null;
        }
    });

    // ── Helpers ─────────────────────────────────────────────────────────────

    function addRow(id, name, email) {
        // Quitar fila vacía si existe
        const emptyRow = tbody.querySelector('.empty-row');
        if (emptyRow) emptyRow.remove();

        const tr = document.createElement('tr');
        tr.dataset.userId = id;
        tr.innerHTML = `
            <td class="px-4 py-2 fw-semibold">
                <i class="fas fa-user-circle me-2" style="color:var(--accent);opacity:.6;font-size:.9rem"></i>
                ${escHtml(name)}
            </td>
            <td class="px-4 py-2" style="font-size:.85rem;color:var(--muted-color)">${escHtml(email)}</td>
            <td class="px-4 py-2 text-end">
                <button type="button"
                        class="btn btn-sm btn-outline-danger btn-remove-user"
                        data-user-id="${id}"
                        data-user-name="${escHtml(name)}"
                        data-user-email="${escHtml(email)}"
                        style="border-radius:7px;font-size:.78rem"
                        title="Quitar usuario">
                    <i class="fas fa-user-minus me-1"></i>Quitar
                </button>
            </td>`;
        tbody.appendChild(tr);
    }

    function checkEmpty() {
        const rows = tbody.querySelectorAll('tr:not(.empty-row)');
        if (rows.length === 0) {
            const tr = document.createElement('tr');
            tr.className = 'empty-row';
            tr.innerHTML = `<td colspan="3" class="text-center py-5" style="color:var(--muted-color)">
                <i class="fas fa-user-slash fa-2x mb-2 d-block" style="opacity:.3"></i>
                <span class="small">Sin usuarios asociados a esta unidad.</span>
            </td>`;
            tbody.appendChild(tr);
        }
    }

    function updateCount(delta) {
        const current = parseInt(countPill?.textContent || '0', 10);
        const next    = Math.max(0, current + delta);

        if (countPill) {
            countPill.textContent    = next;
            countPill.style.background = next > 0 ? 'var(--accent)' : 'var(--badge-light-bg)';
            countPill.style.color      = next > 0 ? '#fff' : 'var(--muted-color)';
        }
        if (countBadge) countBadge.textContent = next;
    }

    function syncSelectVisibility() {
        if (!userSelect) return;
        const realOptions = Array.from(userSelect.options).filter(o => o.value !== '');
        const addArea = document.querySelector('.px-4.py-3[style*="background:var(--badge-light-bg)"]');
        // No ocultar el bloque; dejamos que el select quede vacío — el blur/placeholder lo indica
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function flashMsg(msg, type) {
        const div = document.createElement('div');
        div.className = `alert alert-${type} alert-dismissible fade show`;
        div.style.cssText = `
            position:fixed;top:76px;right:1.5rem;z-index:9999;
            min-width:300px;max-width:420px;
            border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.18);
            font-size:.87rem;`;
        div.innerHTML = `
            <i class="fas fa-${type === 'danger' ? 'exclamation-circle' : 'check-circle'} me-2"></i>
            ${msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(div);
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(div);
            bsAlert?.close();
        }, 4500);
    }
})();
</script>
@endpush
