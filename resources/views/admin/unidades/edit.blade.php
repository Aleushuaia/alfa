@extends('layouts.dashboard')

@section('title', 'Editar Unidad')
@section('page-title', 'Unidades de Trabajo')
@section('breadcrumb', 'Editar Unidad')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="t-card">

            {{-- Cabecera --}}
            <div class="mb-4">
                <h5 style="color:var(--heading-color);font-weight:700;margin-bottom:.25rem">
                    <i class="fas fa-pen me-2" style="color:var(--accent)"></i>Editar Unidad de Trabajo
                </h5>
                <p class="small mb-0" style="color:var(--muted-color)">
                    Modificá la descripción de la unidad <strong>#{{ $unidad->id }}</strong>.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.unidades.update', $unidad) }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="form-label fw-semibold small text-uppercase"
                           style="letter-spacing:.5px;color:var(--body-color);opacity:.7">
                        Descripción
                    </label>
                    <input type="text"
                           name="descripcion"
                           id="descripcion"
                           maxlength="150"
                           class="form-control @error('descripcion') is-invalid @enderror"
                           value="{{ old('descripcion', $unidad->descripcion) }}"
                           required
                           autocomplete="off"
                           placeholder="Descripción de la unidad (máx. 150 caracteres)">
                    @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text mt-1" id="char-count" style="color:var(--muted-color)">
                        0 / 150 caracteres
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('admin.unidades.show', $unidad) }}"
                       class="btn btn-secondary flex-fill"
                       style="border-radius:10px;font-weight:500">
                        <i class="fas fa-arrow-left me-1"></i>Cancelar
                    </a>
                    <button type="submit"
                            class="btn btn-primary flex-fill"
                            style="background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:10px;font-weight:600">
                        <i class="fas fa-save me-1"></i>Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const input = document.getElementById('descripcion');
    const counter = document.getElementById('char-count');
    if (!input || !counter) return;
    function update() {
        const len = input.value.length;
        counter.textContent = `${len} / 150 caracteres`;
        counter.style.color = len >= 140 ? '#ef4444' : 'var(--muted-color)';
    }
    input.addEventListener('input', update);
    update();
})();
</script>
@endpush
