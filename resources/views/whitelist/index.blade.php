@extends('layouts.dashboard')

@section('title', 'Gestión de Whitelist — Alfa')
@section('page-title', 'Gestión de Whitelist de Entidades (Agregadas)')
@section('breadcrumb', 'Whitelist')

@push('styles')
<style>
    .wl-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: #8898aa;
    }
    .wl-empty i { font-size: 2.5rem; margin-bottom: .75rem; display: block; }
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
    #toast-container-wl {
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
                    <i class="fas fa-check-circle me-2 text-success"></i>
                    <span class="fw-semibold">Entidades en la Whitelist</span>
                    <span class="badge bg-success ms-2" id="wl-count">{{ $entries->count() }}</span>
                </div>
                <div class="text-muted" style="font-size:.82rem;">
                    <i class="fas fa-info-circle me-1"></i>
                    Las entidades aquí listadas fueron añadidas manualmente para su reconocimiento en futuros análisis.
                </div>
            </div>

            <div class="card-body p-0">
                @if($entries->isEmpty())
                    <div class="wl-empty">
                        <i class="fas fa-list-ul text-muted"></i>
                        <p class="mb-0">La whitelist está vacía. Seleccione texto en el analizador y agréguelo con clic derecho.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0" id="whitelist-table" style="font-size:.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:42%">Término</th>
                                    <th>Tipo sugerido</th>
                                    <th>Agregado por</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th class="text-center" style="width:70px">Eliminar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entries as $entry)
                                <tr id="wl-row-{{ $entry->id }}" data-id="{{ $entry->id }}">
                                    <td class="fw-medium">{{ $entry->term }}</td>
                                    <td>
                                        @if($entry->entity_type)
                                            <span class="badge badge-type bg-success-subtle text-success border border-success-subtle">{{ $entry->entity_type }}</span>
                                        @else
                                            <span class="text-muted" style="font-size:.8rem;">Sin tipo</span>
                                        @endif
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
                                                title="Eliminar esta entrada de la whitelist">
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
                Al eliminar una entrada, el término dejará de ser reconocido automáticamente en futuros análisis.
            </div>
            @endif
        </div>

    </div>
</div>

<div id="toast-container-wl"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container-wl');
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
        const badge = document.getElementById('wl-count');
        if (!badge) return;
        const remaining = document.querySelectorAll('#whitelist-table tbody tr').length;
        badge.textContent = remaining;
        if (remaining === 0) {
            const tbody = document.querySelector('#whitelist-table tbody');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="6">
                    <div class="wl-empty">
                        <i class="fas fa-list-ul text-muted" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                        <p class="mb-0">La whitelist está vacía.</p>
                    </div>
                </td></tr>`;
            }
        }
    }

    document.querySelectorAll('.btn-delete-entry').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id   = btn.dataset.id;
            const term = btn.dataset.term;

            if (!confirm(`¿Eliminar "${term}" de la whitelist?\n\nEste término dejará de ser reconocido automáticamente.`)) return;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                const res = await fetch(`/whitelist/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Error al eliminar.');

                const row = document.getElementById(`wl-row-${id}`);
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
