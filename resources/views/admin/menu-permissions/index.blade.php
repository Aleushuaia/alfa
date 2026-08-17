@extends('layouts.dashboard')

@section('title', 'Permisos de Menú')
@section('page-title', 'Permisos de Menú por Rol')
@section('breadcrumb', 'Permisos de Menú')

@push('styles')
<style>
/* ── Layout wrapper ──────────────────────────────────────────────────── */
.perm-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
}

/* ── Table ───────────────────────────────────────────────────────────── */
.perm-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .88rem;
}

.perm-table thead th {
    padding: .6rem .9rem;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: var(--muted-color, #64748b);
    background: var(--table-head-bg, rgba(0,0,0,.03));
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    white-space: nowrap;
    text-align: center;
}

.perm-table thead th.col-item {
    text-align: left;
    min-width: 180px;
}

.perm-table thead th.col-section {
    text-align: left;
    background: var(--accent-subtle, rgba(59,130,246,.07));
    color: var(--accent, #3b82f6);
    font-size: .75rem;
    padding-top: .9rem;
}

.perm-table tbody tr {
    transition: background .15s;
}

.perm-table tbody tr:hover {
    background: var(--row-hover, rgba(59,130,246,.04));
}

.perm-table tbody td {
    padding: .55rem .9rem;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    vertical-align: middle;
}

.perm-table tbody td.td-item {
    font-weight: 500;
    color: var(--body-color, #1e293b);
}

.perm-table tbody td.td-item .item-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 7px;
    background: var(--accent-subtle, rgba(59,130,246,.1));
    color: var(--accent, #3b82f6);
    font-size: .8rem;
    margin-right: .55rem;
    flex-shrink: 0;
}

.perm-table tbody td.td-check {
    text-align: center;
}

/* ── Toggle switch ───────────────────────────────────────────────────── */
.toggle-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.toggle-wrap input[type="checkbox"] {
    appearance: none;
    -webkit-appearance: none;
    width: 36px;
    height: 20px;
    border-radius: 20px;
    background: var(--border-color, #cbd5e1);
    cursor: pointer;
    position: relative;
    transition: background .2s;
    flex-shrink: 0;
}

.toggle-wrap input[type="checkbox"]::before {
    content: '';
    position: absolute;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #fff;
    top: 3px;
    left: 3px;
    transition: transform .2s;
    box-shadow: 0 1px 3px rgba(0,0,0,.2);
}

.toggle-wrap input[type="checkbox"]:checked {
    background: var(--accent, #3b82f6);
}

.toggle-wrap input[type="checkbox"]:checked::before {
    transform: translateX(16px);
}

/* ── Role header chips ───────────────────────────────────────────────── */
.role-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .25rem .7rem;
    border-radius: 20px;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .4px;
    background: var(--accent-subtle, rgba(59,130,246,.1));
    color: var(--accent, #3b82f6);
    border: 1px solid rgba(59,130,246,.2);
}

/* ── Section separator row ──────────────────────────────────────────── */
.section-row td {
    background: var(--accent-subtle, rgba(59,130,246,.05)) !important;
    color: var(--accent, #3b82f6) !important;
    font-weight: 700;
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .7px;
    padding: .45rem .9rem !important;
    border-bottom: none !important;
}

/* ── Bulk toggle per-role header ─────────────────────────────────────── */
.bulk-toggle {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: .25rem;
    cursor: pointer;
}

.bulk-toggle .bulk-label {
    font-size: .68rem;
    color: var(--muted-color, #94a3b8);
    font-weight: 500;
}

/* ── Responsive: scroll horizontal ──────────────────────────────────── */
.perm-table-wrap {
    overflow-x: auto;
}

/* ── Save bar ────────────────────────────────────────────────────────── */
.save-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border-color, #e2e8f0);
    background: var(--card-bg, #fff);
    gap: 1rem;
    flex-wrap: wrap;
}

.save-bar .save-hint {
    font-size: .82rem;
    color: var(--muted-color, #64748b);
    display: flex;
    align-items: center;
    gap: .4rem;
}

.btn-save {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .55rem 1.4rem;
    border: none;
    border-radius: 9px;
    background: var(--accent, #3b82f6);
    color: #fff;
    font-size: .9rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity .2s, transform .15s;
}

.btn-save:hover { opacity: .88; transform: translateY(-1px); }
.btn-save:active { transform: translateY(0); }

/* ── Empty state ────────────────────────────────────────────────────── */
.empty-roles {
    padding: 3rem;
    text-align: center;
    color: var(--muted-color, #64748b);
    font-size: .9rem;
}
</style>
@endpush

@section('content')

{{-- ── Alert éxito ──────────────────────────────────────────────────────── --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert"
     style="border-radius:10px;font-size:.9rem">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── Info box ─────────────────────────────────────────────────────────── --}}
<div style="background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.2);border-radius:12px;
            padding:.9rem 1.2rem;margin-bottom:1.5rem;font-size:.85rem;color:var(--body-color);
            display:flex;align-items:flex-start;gap:.75rem">
    <i class="fas fa-circle-info mt-1" style="color:var(--accent,#3b82f6);font-size:1.1rem;flex-shrink:0"></i>
    <div>
        Activá los ítems del menú que cada rol puede ver. El rol <strong>administrador</strong> siempre
        tiene acceso completo y no se muestra aquí. Los cambios se aplican de inmediato.
    </div>
</div>

@if($roles->isEmpty())
    <div class="perm-card">
        <div class="empty-roles">
            <i class="fas fa-users-slash" style="font-size:2rem;opacity:.35;display:block;margin-bottom:.75rem"></i>
            No hay roles adicionales definidos. Creá roles desde
            <a href="{{ route('admin.users.index') }}">Gestión de usuarios</a>.
        </div>
    </div>
@else
<form method="POST" action="{{ route('admin.menu-permissions.update') }}" id="permForm">
@csrf

<div class="perm-card">
    <div class="perm-table-wrap">
        <table class="perm-table">
            <thead>
                <tr>
                    <th class="col-item">Ítem del menú</th>
                    @foreach($roles as $role)
                    <th>
                        <div class="bulk-toggle" data-role="{{ $role->id }}" title="Activar / desactivar todos para este rol">
                            <span class="role-chip">
                                <i class="fas fa-user-tag"></i>
                                {{ ucfirst($role->name) }}
                            </span>
                            <span class="bulk-label">todo/nada</span>
                        </div>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($sections as $sectionName => $items)

                {{-- ── Separador de sección ── --}}
                <tr class="section-row">
                    <td colspan="{{ $roles->count() + 1 }}">
                        <i class="fas fa-layer-group me-1"></i>
                        {{ $sectionName }}
                    </td>
                </tr>

                @foreach($items as $item)
                <tr>
                    <td class="td-item">
                        <div style="display:flex;align-items:center">
                            <span class="item-icon">
                                <i class="{{ $item->icon }}"></i>
                            </span>
                            {{ $item->label }}
                        </div>
                    </td>
                    @foreach($roles as $role)
                    <td class="td-check">
                        <label class="toggle-wrap" title="{{ ucfirst($role->name) }} — {{ $item->label }}">
                            <input type="checkbox"
                                   name="perms[{{ $role->id }}][{{ $item->key }}]"
                                   value="1"
                                   data-role="{{ $role->id }}"
                                   {{ isset($rolePerms[$role->id][$item->permissionName()]) ? 'checked' : '' }}>
                        </label>
                    </td>
                    @endforeach
                </tr>
                @endforeach

                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── Barra de guardar ─────────────────────────────────────────────── --}}
    <div class="save-bar">
        <span class="save-hint">
            <i class="fas fa-shield-halved" style="color:var(--accent,#3b82f6)"></i>
            Los cambios se aplican a todos los usuarios del rol al guardar.
        </span>
        <button type="submit" class="btn-save">
            <i class="fas fa-floppy-disk"></i>
            Guardar permisos
        </button>
    </div>
</div>

</form>
@endif

@endsection

@push('scripts')
<script>
// Bulk toggle: click en el encabezado del rol activa/desactiva todos sus checkboxes
document.querySelectorAll('.bulk-toggle').forEach(function(el) {
    el.addEventListener('click', function() {
        const roleId = el.dataset.role;
        const checks = document.querySelectorAll('input[type="checkbox"][data-role="' + roleId + '"]');
        const anyUnchecked = Array.from(checks).some(c => !c.checked);
        checks.forEach(c => { c.checked = anyUnchecked; });
    });
});
</script>
@endpush
