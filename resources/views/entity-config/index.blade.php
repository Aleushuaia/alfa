@extends('layouts.dashboard')

@section('title', 'Gestión de entidades — Alfa')
@section('page-title', 'Configuración de colores por tipo de entidad')
@section('breadcrumb', 'Gestión de entidades')

@push('styles')
<style>
    .entity-color-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: .75rem 1rem;
        border-radius: 8px;
        transition: background .15s;
    }
    .entity-color-row:hover {
        background: var(--card-bg);
    }
    .entity-color-preview {
        width: 36px;
        height: 36px;
        border-radius: 6px;
        border: 2px solid var(--border);
        flex-shrink: 0;
        transition: background .2s;
    }
    .entity-color-label {
        font-weight: 600;
        font-size: .92rem;
        min-width: 120px;
        color: var(--text);
    }
    .entity-color-type {
        font-size: .75rem;
        color: var(--text-muted);
        font-family: monospace;
        min-width: 60px;
    }
    .entity-color-input {
        width: 50px;
        height: 36px;
        padding: 2px;
        border: 2px solid var(--border);
        border-radius: 6px;
        cursor: pointer;
        background: transparent;
    }
    .entity-color-hex {
        width: 90px;
        font-family: monospace;
        font-size: .85rem;
        text-align: center;
    }
    .btn-reset-color {
        font-size: .75rem;
        padding: .2rem .5rem;
        opacity: .6;
        transition: opacity .15s;
    }
    .btn-reset-color:hover {
        opacity: 1;
    }
</style>
@endpush

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-palette me-2"></i>Colores de tipos de entidades
                </div>
                <span class="badge bg-secondary">{{ count($entityTypes) }} tipos</span>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3" style="font-size:.85rem;">
                    Personalice el color con el que se resaltan las entidades detectadas en el anonimizador.
                    Los cambios se guardan por usuario y se aplican en futuros análisis.
                </p>

                <form method="POST" action="{{ route('entity-config.save') }}" id="colorForm">
                    @csrf

                    <div class="d-flex flex-column gap-2">
                        @foreach($entityTypes as $entity)
                        <div class="entity-color-row" data-type="{{ $entity['type'] }}" data-default="{{ $entity['default'] }}">
                            <div class="entity-color-preview" id="preview-{{ $entity['type'] }}" style="background: {{ $entity['color'] }}"></div>
                            <div class="entity-color-label">{{ $entity['label'] }}</div>
                            <div class="entity-color-type">{{ $entity['type'] }}</div>
                            <input type="color"
                                   name="colors[{{ $entity['type'] }}]"
                                   value="{{ $entity['color'] }}"
                                   class="entity-color-input"
                                   id="color-{{ $entity['type'] }}"
                                   data-type="{{ $entity['type'] }}">
                            <input type="text"
                                   class="form-control form-control-sm entity-color-hex"
                                   value="{{ $entity['color'] }}"
                                   id="hex-{{ $entity['type'] }}"
                                   data-type="{{ $entity['type'] }}"
                                   maxlength="7"
                                   pattern="^#[0-9a-fA-F]{6}$">
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm btn-reset-color"
                                    data-type="{{ $entity['type'] }}"
                                    data-default="{{ $entity['default'] }}"
                                    title="Restaurar color predeterminado">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3" style="border-top: 1px solid var(--border);">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetAll">
                            <i class="fas fa-undo me-1"></i>Restaurar todos los colores
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-save me-1"></i>Guardar configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Sync color picker → hex input → preview
    document.querySelectorAll('.entity-color-input').forEach(input => {
        input.addEventListener('input', (e) => {
            const type = e.target.dataset.type;
            const color = e.target.value;
            document.getElementById('hex-' + type).value = color;
            document.getElementById('preview-' + type).style.background = color;
        });
    });

    // Sync hex input → color picker → preview
    document.querySelectorAll('.entity-color-hex').forEach(input => {
        input.addEventListener('input', (e) => {
            const type = e.target.dataset.type;
            let val = e.target.value.trim();
            if (!val.startsWith('#')) val = '#' + val;
            if (/^#[0-9a-fA-F]{6}$/.test(val)) {
                document.getElementById('color-' + type).value = val;
                document.getElementById('preview-' + type).style.background = val;
            }
        });
        input.addEventListener('blur', (e) => {
            const type = e.target.dataset.type;
            // Sync back from color picker on blur for invalid inputs
            e.target.value = document.getElementById('color-' + type).value;
        });
    });

    // Reset individual color
    document.querySelectorAll('.btn-reset-color').forEach(btn => {
        btn.addEventListener('click', () => {
            const type = btn.dataset.type;
            const def = btn.dataset.default;
            document.getElementById('color-' + type).value = def;
            document.getElementById('hex-' + type).value = def;
            document.getElementById('preview-' + type).style.background = def;
        });
    });

    // Reset all colors
    document.getElementById('btnResetAll')?.addEventListener('click', () => {
        document.querySelectorAll('.entity-color-row').forEach(row => {
            const type = row.dataset.type;
            const def = row.dataset.default;
            document.getElementById('color-' + type).value = def;
            document.getElementById('hex-' + type).value = def;
            document.getElementById('preview-' + type).style.background = def;
        });
    });
});
</script>
@endpush
