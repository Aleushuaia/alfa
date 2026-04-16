@extends('layouts.dashboard')

@section('title', 'Gestión de Blacklist — Alfa')
@section('page-title', 'Gestión de Blacklist de Entidades')
@section('breadcrumb', 'Blacklist')

@push('styles')
<style>
    .bl-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: #8898aa;
    }
    .bl-empty i { font-size: 2.5rem; margin-bottom: .75rem; display: block; }
    .badge-type {
        font-size: .72rem;
        letter-spacing: .03em;
    }
    .btn-delete-entry {
        padding: 2px 8px;
        font-size: .78rem;
        line-height: 1.4;
    }
    tr.removing {
        opacity: 0;
        transition: opacity .3s ease;
    }
    #toast-container-bl {
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

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-11">

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <i class="fas fa-ban me-2 text-danger"></i>
                    <span class="fw-semibold">Entidades en la Blacklist</span>
                    <span class="badge bg-secondary ms-2" id="bl-count">{{ $entries->count() }}</span>
                </div>
                <div class="text-muted" style="font-size:.82rem;">
                    <i class="fas fa-info-circle me-1"></i>
                    Las entidades aquí listadas son ignoradas automáticamente en los análisis de texto.
                </div>
            </div>

            <div class="card-body p-0">
                @if($entries->isEmpty())
                    <div class="bl-empty">
                        <i class="fas fa-check-circle text-success"></i>
                        <p class="mb-0">La blacklist está vacía. No hay entidades ignoradas.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0" id="blacklist-table" style="font-size:.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40%">Término</th>
                                    <th>Tipo</th>
                                    <th>Modo</th>
                                    <th>Agregado por</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th class="text-center" style="width:70px">Eliminar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entries as $entry)
                                <tr id="bl-row-{{ $entry->id }}" data-id="{{ $entry->id }}">
                                    <td class="fw-medium">{{ $entry->term }}</td>
                                    <td>
                                        @if($entry->entity_type)
                                            <span class="badge badge-type bg-secondary">{{ $entry->entity_type }}</span>
                                        @else
                                            <span class="text-muted" style="font-size:.8rem;">Todos</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-type bg-light text-dark border">{{ $entry->match_mode ?? 'exact' }}</span>
                                    </td>
                                    <td class="text-muted">{{ $entry->added_by ?? '—' }}</td>
                                    <td class="text-muted" style="white-space:nowrap;">
                                        {{ $entry->created_at ? $entry->created_at->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td>
                                        @if($entry->active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Activa</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-outline-danger btn-delete-entry"
                                                data-id="{{ $entry->id }}"
                                                data-term="{{ $entry->term }}"
                                                title="Eliminar esta entrada de la blacklist">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if($entries->isNotEmpty())
            <div class="card-footer text-muted" style="font-size:.78rem;">
                <i class="fas fa-trash-alt me-1"></i>
                Al eliminar una entrada, la entidad volverá a aparecer en los próximos análisis de texto.
            </div>
            @endif
        </div>

    </div>
</div>

<div id="toast-container-bl"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container-bl');
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} shadow py-2 px-3 mb-0`;
        toast.style.cssText = 'min-width:260px;max-width:420px;font-size:.87rem;animation:fadeInUp .2s ease;';
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity .3s';
            setTimeout(() => toast.remove(), 320);
        }, 4500);
    }

    function updateCount() {
        const badge = document.getElementById('bl-count');
        if (!badge) return;
        const remaining = document.querySelectorAll('#blacklist-table tbody tr').length;
        badge.textContent = remaining;
        if (remaining === 0) {
            const tbody = document.querySelector('#blacklist-table tbody');
            if (tbody) {
                const colspan = 7;
                tbody.innerHTML = `<tr><td colspan="${colspan}">
                    <div class="bl-empty">
                        <i class="fas fa-check-circle text-success" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                        <p class="mb-0">La blacklist está vacía. No hay entidades ignoradas.</p>
                    </div>
                </td></tr>`;
            }
        }
    }

    document.querySelectorAll('.btn-delete-entry').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id   = btn.dataset.id;
            const term = btn.dataset.term;

            if (!confirm(`¿Eliminar "${term}" de la blacklist?\n\nEsta entidad volverá a aparecer en futuros análisis.`)) return;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                const res = await fetch(`/blacklist/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (!res.ok || !data.success) throw new Error(data.message || 'Error al eliminar.');

                // Fade out and remove the row
                const row = document.getElementById(`bl-row-${id}`);
                if (row) {
                    row.classList.add('removing');
                    setTimeout(() => { row.remove(); updateCount(); }, 320);
                }

                showToast(data.message, 'success');

            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-times"></i>';
                showToast('❌ ' + err.message, 'danger');
            }
        });
    });
});
</script>
@endpush
