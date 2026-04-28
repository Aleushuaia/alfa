@extends('layouts.dashboard')

@section('title', 'Gestión de Prompts')
@section('page-title', 'Gestión de Prompts')
@section('breadcrumb', 'Prompts')

@push('styles')
<style>
.prompt-wrap {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1.5rem;
    align-items: start;
}
@media (max-width: 900px) {
    .prompt-wrap { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius:10px;font-size:.9rem">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="prompt-wrap">

    {{-- ── Panel izquierdo: formulario crear ────────────────────────────── --}}
    <div class="t-card">
        <div class="t-card-header">
            <i class="fas fa-plus-circle me-2" style="color:var(--accent)"></i>Nuevo prompt
        </div>
        <div class="t-card-body">
            <form method="POST" action="{{ route('admin.prompts.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-uppercase"
                           style="letter-spacing:.5px;color:var(--body-color);opacity:.7">
                        Descripción <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           name="descripcion"
                           class="form-control @error('descripcion') is-invalid @enderror"
                           value="{{ old('descripcion') }}"
                           required
                           maxlength="150"
                           placeholder="Ej: Extracción de sujetos procesales v1">
                    @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text" style="font-size:.75rem;color:var(--muted-color)">
                        Identificador funcional único. Se mostrará en los dropdowns de la UI.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-uppercase"
                           style="letter-spacing:.5px;color:var(--body-color);opacity:.7">
                        Contenido <span class="text-danger">*</span>
                    </label>
                    <textarea name="contenido"
                              class="form-control @error('contenido') is-invalid @enderror"
                              rows="10"
                              required
                              placeholder="Escribí el prompt completo. Usá @{{texto}} como placeholder del texto extraído del PDF.">{{ old('contenido') }}</textarea>
                    @error('contenido')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text" style="font-size:.75rem;color:var(--muted-color)">
                        Usá <code>@{{texto}}</code> como placeholder del texto extraído del PDF.
                    </div>
                </div>

                <button type="submit"
                        class="btn btn-primary w-100"
                        style="background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:10px;padding:.6rem;font-weight:600;">
                    <i class="fas fa-save me-2"></i>Crear prompt
                </button>
            </form>
        </div>
    </div>

    {{-- ── Panel derecho: listado ────────────────────────────────────────── --}}
    <div class="t-card">
        <div class="t-card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2" style="color:var(--accent)"></i>Prompts registrados</span>
            <span class="badge" style="background:var(--accent);font-size:.75rem">{{ $prompts->count() }}</span>
        </div>
        <div class="t-card-body p-0">
            @if($prompts->isEmpty())
                <div class="text-center py-5" style="color:var(--body-color);opacity:.5">
                    <i class="fas fa-file-alt fa-2x mb-2"></i>
                    <p class="mb-0">No hay prompts registrados.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="color:var(--table-color)">
                        <thead>
                            <tr style="background:var(--table-head-bg);color:var(--table-head-color);font-size:.78rem;text-transform:uppercase;letter-spacing:.5px">
                                <th class="px-3 py-2">Descripción</th>
                                <th class="px-3 py-2">Creado</th>
                                <th class="px-3 py-2 text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prompts as $p)
                            <tr>
                                <td class="px-3 py-2 fw-semibold">
                                    <i class="fas fa-file-code me-1" style="color:var(--accent);opacity:.6"></i>
                                    {{ $p->descripcion }}
                                </td>
                                <td class="px-3 py-2" style="font-size:.83rem;color:var(--muted-color)">
                                    {{ $p->created_at->format('d/m/Y') }}
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary me-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal{{ $p->id }}"
                                            style="border-radius:8px;font-size:.78rem"
                                            title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form method="POST"
                                          action="{{ route('admin.prompts.destroy', $p) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('¿Seguro que deseas eliminar el prompt «{{ $p->descripcion }}»?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                                style="border-radius:8px;font-size:.78rem"
                                                title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            {{-- ── Modal de edición ─────────────────────── --}}
                            <div class="modal fade" id="editModal{{ $p->id }}" tabindex="-1"
                                 aria-labelledby="editLabel{{ $p->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content" style="background:var(--card-bg);color:var(--body-color);border:1px solid var(--card-border);border-radius:14px">
                                        <div class="modal-header" style="border-bottom:1px solid var(--card-border)">
                                            <h5 class="modal-title" id="editLabel{{ $p->id }}">
                                                <i class="fas fa-pen me-2" style="color:var(--accent)"></i>Editar prompt
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('admin.prompts.update', $p) }}">
                                            @csrf @method('PUT')
                                            <div class="modal-body">

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small text-uppercase"
                                                           style="letter-spacing:.5px;color:var(--body-color);opacity:.7">
                                                        Descripción <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text"
                                                           name="descripcion"
                                                           class="form-control"
                                                           value="{{ $p->descripcion }}"
                                                           required
                                                           maxlength="150">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small text-uppercase"
                                                           style="letter-spacing:.5px;color:var(--body-color);opacity:.7">
                                                        Contenido <span class="text-danger">*</span>
                                                    </label>
                                                    <textarea name="contenido"
                                                              class="form-control"
                                                              rows="12"
                                                              required>{{ $p->contenido }}</textarea>
                                                    <div class="form-text" style="font-size:.75rem;color:var(--muted-color)">
                                                        Usá <code>@{{texto}}</code> donde debe insertarse el texto del PDF.
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="modal-footer" style="border-top:1px solid var(--card-border)">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit"
                                                        class="btn btn-primary btn-sm"
                                                        style="background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:8px;font-weight:600">
                                                    <i class="fas fa-save me-1"></i>Guardar cambios
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            {{-- ── /Modal ───────────────────────────────── --}}

                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
