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
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="color:var(--table-color)">
                            <thead>
                                <tr style="background:var(--table-head-bg);color:var(--table-head-color);font-size:.78rem;text-transform:uppercase;letter-spacing:.5px">
                                    <th class="px-3 py-2">Usuario</th>
                                    <th class="px-3 py-2">Email</th>
                                    <th class="px-3 py-2">Rol</th>
                                    <th class="px-3 py-2">Unidades</th>
                                    <th class="px-3 py-2 text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $u)
                                <tr>
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
                                        <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="d-inline"
                                              onsubmit="return confirm('¿Seguro que deseas eliminar a {{ $u->name }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    style="border-radius:8px;font-size:.78rem"
                                                    title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
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
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px">
            <form method="POST" action="" id="formResetPwd">
                @csrf
                @method('PUT')
                <div class="modal-header" style="border-bottom:1px solid var(--card-border);padding:1rem 1.25rem .75rem">
                    <h5 class="modal-title" style="color:var(--heading-color);font-size:.95rem;font-weight:700">
                        <i class="fas fa-key me-2" style="color:var(--accent)"></i>Resetear contraseña
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:1.25rem">
                    <p class="small mb-3" style="color:var(--muted-color)">
                        Estableciendo nueva contraseña para: <strong id="resetPwdUserName">—</strong>
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" style="color:var(--body-color);opacity:.75;text-transform:uppercase;letter-spacing:.4px;font-size:.68rem">Nueva contraseña</label>
                        <input type="password" name="password" class="form-control" required minlength="8" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold" style="color:var(--body-color);opacity:.75;text-transform:uppercase;letter-spacing:.4px;font-size:.68rem">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8" placeholder="Repetir contraseña" autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);padding:.75rem 1.25rem">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius:8px">Cancelar</button>
                    <button type="submit" class="btn btn-sm" style="background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:8px;font-weight:600">
                        <i class="fas fa-save me-1"></i>Guardar contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
