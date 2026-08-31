@extends('layouts.dashboard')

@section('title', 'Gestión de usuarios y roles')
@section('page-title', 'Gestión de usuarios y roles')
@section('breadcrumb', 'Usuarios y roles')

@section('content')
<div class="row g-4">

    {{-- ── Card: Crear usuario ──────────────────────────────────────────────── --}}
    <div class="col-lg-5">
        <div class="t-card h-100">
            <div class="t-card-header">
                <i class="fas fa-user-plus me-2" style="color:var(--accent)"></i>Nuevo usuario
            </div>
            <div class="t-card-body">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-uppercase" style="letter-spacing:.5px;color:var(--body-color);opacity:.7">Nombre completo</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required placeholder="Nombre y apellido">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-uppercase" style="letter-spacing:.5px;color:var(--body-color);opacity:.7">Correo electrónico</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" required placeholder="nombre@ejemplo.com">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-uppercase" style="letter-spacing:.5px;color:var(--body-color);opacity:.7">Contraseña</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                               required placeholder="Mínimo 8 caracteres" minlength="8">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-uppercase" style="letter-spacing:.5px;color:var(--body-color);opacity:.7">Rol</label>
                        <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                            <option value="">Seleccionar rol…</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100" style="background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:10px;padding:.6rem;font-weight:600;">
                        <i class="fas fa-save me-2"></i>Crear usuario
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Card: Lista de usuarios ──────────────────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="t-card h-100">
            <div class="t-card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users me-2" style="color:var(--accent)"></i>Usuarios registrados</span>
                <span class="badge" style="background:var(--accent);font-size:.75rem">{{ $users->count() }}</span>
            </div>
            <div class="t-card-body p-0">
                @if($users->isEmpty())
                    <div class="text-center py-5" style="color:var(--body-color);opacity:.5">
                        <i class="fas fa-user-slash fa-2x mb-2"></i>
                        <p class="mb-0">No hay usuarios registrados.</p>
                    </div>
                @else
                    @php
                        $unidadesFiltro = $users->flatMap->unidades->unique('id')->sortBy('descripcion')->values();
                    @endphp

                    {{-- ── Filtros ─────────────────────────────────────────── --}}
                    <div class="px-3 py-2 d-flex flex-wrap align-items-center gap-2"
                         style="border-bottom:1px solid var(--card-border);background:var(--table-head-bg)">
                        <div class="position-relative flex-grow-1" style="min-width:150px">
                            <i class="fas fa-magnifying-glass position-absolute" style="left:.65rem;top:50%;transform:translateY(-50%);font-size:.72rem;color:var(--muted-color)"></i>
                            <input type="text" id="filterName" class="form-control form-control-sm"
                                   placeholder="Nombre…" autocomplete="off"
                                   style="padding-left:1.9rem;border-radius:8px;font-size:.8rem">
                        </div>
                        <div class="position-relative flex-grow-1" style="min-width:150px">
                            <i class="fas fa-at position-absolute" style="left:.65rem;top:50%;transform:translateY(-50%);font-size:.72rem;color:var(--muted-color)"></i>
                            <input type="text" id="filterEmail" class="form-control form-control-sm"
                                   placeholder="Email…" autocomplete="off"
                                   style="padding-left:1.9rem;border-radius:8px;font-size:.8rem">
                        </div>
                        <select id="filterUnidad" class="form-select form-select-sm flex-grow-1"
                                style="min-width:150px;border-radius:8px;font-size:.8rem">
                            <option value="">Todas las unidades</option>
                            @foreach($unidadesFiltro as $un)
                                <option value="{{ $un->id }}">{{ $un->descripcion }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="filterClear" class="btn btn-sm btn-outline-secondary"
                                style="border-radius:8px;font-size:.75rem" title="Limpiar filtros">
                            <i class="fas fa-xmark"></i>
                        </button>
                    </div>

                    {{-- ── Barra de acciones masivas ───────────────────────── --}}
                    <div id="bulkBar" class="px-3 py-2 d-flex align-items-center justify-content-between"
                         style="border-bottom:1px solid var(--card-border);background:rgba(220,53,69,.06);display:none !important">
                        <span class="small" style="color:var(--body-color)">
                            <i class="fas fa-check-double me-1" style="color:#dc3545"></i>
                            <strong id="bulkCount">0</strong> seleccionado(s)
                        </span>
                        <button type="button" id="btnBulkDelete" class="btn btn-sm btn-danger"
                                style="border-radius:8px;font-size:.78rem;font-weight:600"
                                data-bs-toggle="modal" data-bs-target="#modalBulkDelete">
                            <i class="fas fa-trash-alt me-1"></i>Eliminar seleccionados
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="color:var(--table-color)">
                            <thead>
                                <tr style="background:var(--table-head-bg);color:var(--table-head-color);font-size:.78rem;text-transform:uppercase;letter-spacing:.5px">
                                    <th class="ps-3 py-2" style="width:38px">
                                        <input type="checkbox" id="selectAllUsers" class="form-check-input" title="Seleccionar todos">
                                    </th>
                                    <th class="px-3 py-2">Usuario</th>
                                    <th class="px-3 py-2">Email</th>
                                    <th class="px-3 py-2">Rol</th>
                                    <th class="px-3 py-2">Unidades</th>
                                    <th class="px-3 py-2 text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $u)
                                <tr class="user-row"
                                    data-name="{{ Str::lower($u->name) }}"
                                    data-email="{{ Str::lower($u->email) }}"
                                    data-unidades="{{ $u->unidades->pluck('id')->implode(',') }}">
                                    <td class="ps-3 py-2">
                                        @if($u->id !== auth()->id())
                                            <input type="checkbox" class="form-check-input user-check"
                                                   value="{{ $u->id }}" data-user-name="{{ $u->name }}">
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 fw-semibold">
                                        <i class="fas fa-user-circle me-1" style="color:var(--accent);opacity:.6"></i>
                                        {{ $u->name }}
                                    </td>
                                    <td class="px-3 py-2" style="font-size:.85rem">{{ $u->email }}</td>
                                    <td class="px-3 py-2">
                                        @foreach($u->roles as $role)
                                            <span class="badge rounded-pill" style="background:{{ $role->name === 'administrador' ? 'var(--accent)' : 'var(--accent2)' }};font-size:.72rem">
                                                {{ ucfirst($role->name) }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-3 py-2">
                                        @if($u->unidades_count > 0)
                                            <span class="badge rounded-pill"
                                                  style="background:var(--accent);font-size:.72rem;cursor:pointer"
                                                  data-bs-toggle="popover"
                                                  data-bs-trigger="focus"
                                                  data-bs-placement="left"
                                                  data-bs-html="true"
                                                  data-bs-title="Unidades de trabajo"
                                                  data-bs-content="{{ $u->unidades->map(fn($un) => '<span class=\'d-block\'>• ' . e($un->descripcion) . '</span>')->implode('') }}"
                                                  tabindex="0">
                                                <i class="fas fa-sitemap me-1"></i>{{ $u->unidades_count }}
                                            </span>
                                        @else
                                            <span class="text-muted" style="font-size:.78rem">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                                data-bs-toggle="modal" data-bs-target="#editModal{{ $u->id }}"
                                                style="border-radius:8px;font-size:.78rem"
                                                title="Editar">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-warning me-1 btn-reset-pwd"
                                                data-bs-toggle="modal" data-bs-target="#modalResetPwd"
                                                data-user-id="{{ $u->id }}"
                                                data-user-name="{{ $u->name }}"
                                                style="border-radius:8px;font-size:.78rem"
                                                title="Resetear contraseña">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        @if($u->id !== auth()->id())
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-delete-user"
                                                data-bs-toggle="modal" data-bs-target="#modalDeleteUser"
                                                data-user-name="{{ $u->name }}"
                                                data-user-email="{{ $u->email }}"
                                                data-action="{{ route('admin.users.destroy', $u) }}"
                                                style="border-radius:8px;font-size:.78rem"
                                                title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        @endif
                                    </td>
                                </tr>

                                {{-- ── Modal de edición ──────────────────── --}}
                                <div class="modal fade" id="editModal{{ $u->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content" style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px">
                                            <form method="POST" action="{{ route('admin.users.update', $u) }}">
                                                @csrf @method('PUT')
                                                <div class="modal-header" style="border-bottom:1px solid var(--card-border)">
                                                    <h5 class="modal-title" style="color:var(--heading-color);font-size:.95rem">
                                                        <i class="fas fa-user-edit me-2" style="color:var(--accent)"></i>Editar usuario
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold" style="color:var(--body-color);opacity:.7">Nombre</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $u->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold" style="color:var(--body-color);opacity:.7">Email</label>
                                                        <input type="email" name="email" class="form-control" value="{{ $u->email }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold" style="color:var(--body-color);opacity:.7">Contraseña <small>(dejar vacío para no cambiar)</small></label>
                                                        <input type="password" name="password" class="form-control" placeholder="Nueva contraseña" minlength="8">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold" style="color:var(--body-color);opacity:.7">Rol</label>
                                                        <select name="role" class="form-select" required>
                                                            @foreach($roles as $role)
                                                                <option value="{{ $role->name }}" {{ $u->hasRole($role->name) ? 'selected' : '' }}>
                                                                    {{ ucfirst($role->name) }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer" style="border-top:1px solid var(--card-border)">
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius:8px">Cancelar</button>
                                                    <button type="submit" class="btn btn-sm btn-primary"
                                                            style="background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:8px;font-weight:600">
                                                        <i class="fas fa-save me-1"></i>Guardar
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                <tr id="noResultsRow" style="display:none">
                                    <td colspan="6" class="text-center py-4" style="color:var(--muted-color);font-size:.85rem">
                                        <i class="fas fa-filter-circle-xmark me-1"></i>Ningún usuario coincide con los filtros.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Card: Roles y permisos ──────────────────────────────────────────────── --}}
<div class="row g-4 mt-1">
    <div class="col-12">
        <div class="t-card">
            <div class="t-card-header">
                <i class="fas fa-shield-alt me-2" style="color:var(--accent)"></i>Roles y permisos
            </div>
            <div class="t-card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0" style="color:var(--table-color)">
                        <thead>
                            <tr style="background:var(--table-head-bg);color:var(--table-head-color);font-size:.78rem;text-transform:uppercase;letter-spacing:.5px">
                                <th class="px-3 py-2">Rol</th>
                                <th class="px-3 py-2">Permisos asignados</th>
                                <th class="px-3 py-2 text-end">Usuarios</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                            <tr>
                                <td class="px-3 py-2 fw-semibold">
                                    <i class="fas fa-user-tag me-1" style="color:var(--accent);opacity:.6"></i>
                                    {{ ucfirst($role->name) }}
                                </td>
                                <td class="px-3 py-2">
                                    @foreach($role->permissions as $perm)
                                        <span class="badge rounded-pill me-1 mb-1" style="background:rgba(59,130,246,.15);color:var(--accent);font-size:.7rem;font-weight:500">
                                            {{ $perm->name }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="badge" style="background:var(--accent2);font-size:.72rem">{{ $role->users->count() }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- ── Modal: Reset contraseña de usuario (admin) ─────────────────────────── --}}
<div class="modal fade" id="modalResetPwd" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
        <div class="modal-content" style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px">
            <form method="POST" action="" id="formResetPwd">
                @csrf
                @method('PUT')
                <div class="modal-header" style="border-bottom:1px solid var(--card-border);padding:1rem 1.5rem .85rem">
                    <h5 class="modal-title" style="color:var(--heading-color);font-size:.95rem;font-weight:700">
                        <i class="fas fa-key me-2" style="color:var(--accent)"></i>Resetear contraseña
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.25rem 1.5rem">
                    <p class="small mb-3" style="color:var(--muted-color)">
                        Estableciendo nueva contraseña para: <strong id="resetPwdUserName">—</strong>
                    </p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold" style="color:var(--body-color);opacity:.75;text-transform:uppercase;letter-spacing:.4px;font-size:.68rem">Nueva contraseña</label>
                            <input type="password" name="password" id="resetPwdField" class="form-control" required minlength="8" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold" style="color:var(--body-color);opacity:.75;text-transform:uppercase;letter-spacing:.4px;font-size:.68rem">Confirmar contraseña</label>
                            <input type="password" name="password_confirmation" id="resetPwdConfirmField" class="form-control" required minlength="8" placeholder="Repetir contraseña" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="mt-2 text-end">
                        <a href="#" id="genPwdLink" style="font-size:.72rem;color:var(--accent);text-decoration:none">
                            <i class="fas fa-wand-magic-sparkles me-1"></i>Generar contraseña automática
                        </a>
                    </div>
                </div>
                <div class="modal-footer flex-nowrap" style="border-top:1px solid var(--card-border);padding:.85rem 1.5rem;gap:.5rem">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;white-space:nowrap">Cancelar</button>
                    <button type="submit" class="btn btn-sm" style="background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:8px;font-weight:600;white-space:nowrap">
                        <i class="fas fa-save me-1"></i>Guardar contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Modal: Confirmar eliminación de usuario ────────────────────────────── --}}
<div class="modal fade" id="modalDeleteUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;overflow:hidden">
            <form method="POST" action="" id="formDeleteUser">
                @csrf
                @method('DELETE')
                <div class="modal-body text-center" style="padding:1.75rem 1.5rem 1.25rem">
                    <div class="d-inline-flex align-items-center justify-content-center mb-3"
                         style="width:56px;height:56px;border-radius:50%;background:rgba(220,53,69,.12)">
                        <i class="fas fa-triangle-exclamation" style="color:#dc3545;font-size:1.4rem"></i>
                    </div>
                    <h5 class="modal-title mb-2" style="color:var(--heading-color);font-size:1rem;font-weight:700">
                        Eliminar usuario
                    </h5>
                    <p class="small mb-1" style="color:var(--body-color);opacity:.85">
                        ¿Confirmás que deseás eliminar a <strong id="deleteUserName">—</strong>?
                    </p>
                    <p class="mb-0" style="color:var(--muted-color);font-size:.78rem" id="deleteUserEmail">—</p>
                    <p class="mb-0 mt-2" style="color:#dc3545;font-size:.75rem">
                        <i class="fas fa-circle-info me-1"></i>Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer justify-content-center" style="border-top:1px solid var(--card-border);padding:.85rem 1.25rem;gap:.5rem">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;min-width:96px">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-danger" id="btnConfirmDeleteUser" style="border-radius:8px;min-width:96px;font-weight:600">
                        <i class="fas fa-trash-alt me-1"></i>Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Modal: Confirmar eliminación masiva ────────────────────────────────── --}}
<div class="modal fade" id="modalBulkDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
        <div class="modal-content" style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;overflow:hidden">
            <form method="POST" action="{{ route('admin.users.bulk-destroy') }}" id="formBulkDelete">
                @csrf
                @method('DELETE')
                <div class="modal-body" style="padding:1.75rem 1.5rem 1.25rem">
                    <div class="text-center">
                        <div class="d-inline-flex align-items-center justify-content-center mb-3"
                             style="width:56px;height:56px;border-radius:50%;background:rgba(220,53,69,.12)">
                            <i class="fas fa-triangle-exclamation" style="color:#dc3545;font-size:1.4rem"></i>
                        </div>
                        <h5 class="modal-title mb-2" style="color:var(--heading-color);font-size:1rem;font-weight:700">
                            Eliminar usuarios seleccionados
                        </h5>
                        <p class="small mb-2" style="color:var(--body-color);opacity:.85">
                            ¿Confirmás que deseás eliminar los <strong id="bulkModalCount">0</strong> usuarios seleccionados?
                        </p>
                    </div>
                    <ul id="bulkModalList" class="small mb-2"
                        style="color:var(--muted-color);max-height:160px;overflow:auto;padding-left:1.2rem"></ul>
                    <p class="mb-0 text-center" style="color:#dc3545;font-size:.75rem">
                        <i class="fas fa-circle-info me-1"></i>Esta acción no se puede deshacer.
                    </p>
                    <div id="bulkHiddenInputs"></div>
                </div>
                <div class="modal-footer justify-content-center" style="border-top:1px solid var(--card-border);padding:.85rem 1.25rem;gap:.5rem">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;min-width:96px">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-danger" id="btnConfirmBulkDelete" style="border-radius:8px;min-width:110px;font-weight:600">
                        <i class="fas fa-trash-alt me-1"></i>Eliminar todos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── Selección múltiple y eliminación masiva ─────────────────────
(function() {
    var selectAll = document.getElementById('selectAllUsers');
    if (!selectAll) return;

    var bulkBar   = document.getElementById('bulkBar');
    var bulkCount = document.getElementById('bulkCount');
    var checks    = Array.prototype.slice.call(document.querySelectorAll('.user-check'));

    function selected() {
        return checks.filter(function(c) { return c.checked && c.closest('tr').style.display !== 'none'; });
    }

    function refresh() {
        var sel = selected();
        bulkCount.textContent = sel.length;
        bulkBar.style.setProperty('display', sel.length ? 'flex' : 'none', 'important');

        var visible = checks.filter(function(c) { return c.closest('tr').style.display !== 'none'; });
        selectAll.checked = visible.length > 0 && sel.length === visible.length;
        selectAll.indeterminate = sel.length > 0 && sel.length < visible.length;
    }

    checks.forEach(function(c) { c.addEventListener('change', refresh); });

    selectAll.addEventListener('change', function() {
        checks.forEach(function(c) {
            if (c.closest('tr').style.display !== 'none') c.checked = selectAll.checked;
        });
        refresh();
    });

    // Al aplicar filtros, revalidar la selección visible
    ['filterName', 'filterEmail', 'filterUnidad', 'filterClear'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener(id === 'filterUnidad' ? 'change' : 'input', function() { setTimeout(refresh, 0); });
    });
    var fc = document.getElementById('filterClear');
    if (fc) fc.addEventListener('click', function() { setTimeout(refresh, 0); });

    // Preparar el modal de confirmación
    document.getElementById('modalBulkDelete').addEventListener('show.bs.modal', function() {
        var sel = selected();
        document.getElementById('bulkModalCount').textContent = sel.length;

        var list = document.getElementById('bulkModalList');
        var hidden = document.getElementById('bulkHiddenInputs');
        list.innerHTML = '';
        hidden.innerHTML = '';

        sel.forEach(function(c) {
            var li = document.createElement('li');
            li.textContent = c.dataset.userName;
            list.appendChild(li);

            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = c.value;
            hidden.appendChild(input);
        });
    });

    document.getElementById('formBulkDelete').addEventListener('submit', function(e) {
        if (!document.querySelector('#bulkHiddenInputs input')) { e.preventDefault(); return; }
        var b = document.getElementById('btnConfirmBulkDelete');
        b.disabled = true;
        b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminando…';
    });
})();

// ── Filtros de la lista de usuarios ──────────────────────────────
(function() {
    var fName   = document.getElementById('filterName');
    var fEmail  = document.getElementById('filterEmail');
    var fUnidad = document.getElementById('filterUnidad');
    var fClear  = document.getElementById('filterClear');
    if (!fName) return;

    var rows    = Array.prototype.slice.call(document.querySelectorAll('tr.user-row'));
    var noRes   = document.getElementById('noResultsRow');

    function apply() {
        var n = fName.value.trim().toLowerCase();
        var e = fEmail.value.trim().toLowerCase();
        var u = fUnidad.value;
        var visible = 0;

        rows.forEach(function(row) {
            var okN = !n || row.dataset.name.indexOf(n) !== -1;
            var okE = !e || row.dataset.email.indexOf(e) !== -1;
            var okU = !u || (',' + row.dataset.unidades + ',').indexOf(',' + u + ',') !== -1;
            var show = okN && okE && okU;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (noRes) noRes.style.display = visible ? 'none' : '';
    }

    [fName, fEmail].forEach(function(el) { el.addEventListener('input', apply); });
    fUnidad.addEventListener('change', apply);
    fClear.addEventListener('click', function() {
        fName.value = ''; fEmail.value = ''; fUnidad.value = '';
        apply();
    });
})();

// Modal eliminar usuario: cargar datos y action dinámicamente
document.querySelectorAll('.btn-delete-user').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('deleteUserName').textContent  = this.dataset.userName;
        document.getElementById('deleteUserEmail').textContent = this.dataset.userEmail;
        document.getElementById('formDeleteUser').action       = this.dataset.action;
    });
});

// Evitar doble envío
document.getElementById('formDeleteUser').addEventListener('submit', function() {
    var b = document.getElementById('btnConfirmDeleteUser');
    b.disabled = true;
    b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Eliminando…';
});
</script>
@endpush

@push('scripts')
<script>
// Inicializar popovers de unidades
document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function(el) {
    new bootstrap.Popover(el);
});

// Modal reset password: ajustar action dinámicamente
document.querySelectorAll('.btn-reset-pwd').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var userId   = this.dataset.userId;
        var userName = this.dataset.userName;
        document.getElementById('resetPwdUserName').textContent = userName;
        document.getElementById('formResetPwd').action = '/admin/users/' + userId + '/reset-password';
    });
});

// Generar contraseña automática: "Alfa" + 4 caracteres alfanuméricos aleatorios
(function() {
    var link = document.getElementById('genPwdLink');
    if (!link) return;
    link.addEventListener('click', function(e) {
        e.preventDefault();
        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var suffix = '';
        for (var i = 0; i < 4; i++) {
            suffix += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        var pwd = 'Alfa' + suffix;
        var f1 = document.getElementById('resetPwdField');
        var f2 = document.getElementById('resetPwdConfirmField');
        f1.type = f2.type = 'text';
        f1.value = f2.value = pwd;
    });
})();
</script>
@endpush

@push('scripts')
<script>
    // Activar popovers de Bootstrap para mostrar las unidades del usuario
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
            new bootstrap.Popover(el, { sanitize: false });
        });
    });
</script>
@endpush
