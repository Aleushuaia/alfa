{{-- resources/views/dashboard/partials/tabla_notificaciones.blade.php --}}
{{-- Widget 5: Notificaciones con sparklines --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pb-0 d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="fas fa-bell text-info me-2"></i>
            Notificaciones por Tipo
        </h6>
        <span class="badge bg-info-subtle text-info">
            Tendencia últimos 6 meses
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tipo de Notificación</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">% del Total</th>
                        <th class="text-center" style="min-width:120px;">Tendencia (6m)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notificaciones as $i => $n)
                    <tr>
                        <td class="ps-3">
                            <i class="fas fa-circle fa-xs text-info me-1"></i>
                            {{ $n['tipo'] }}
                        </td>
                        <td class="text-end fw-semibold">
                            {{ number_format($n['cantidad']) }}
                        </td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <div class="progress flex-grow-1" style="height:6px;max-width:80px;">
                                    <div class="progress-bar bg-info"
                                         role="progressbar"
                                         style="width: {{ $n['porcentaje'] }}%;"
                                         aria-valuenow="{{ $n['porcentaje'] }}"
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <span class="text-nowrap small">{{ $n['porcentaje'] }}%</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <div id="sparkline-{{ $i }}" style="min-width:110px;"></div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-2"></i>
                            Sin datos para el período seleccionado
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const data = window.dashboardData.notificaciones;

    data.forEach((n, i) => {
        const el = document.querySelector('#sparkline-' + i);
        if (!el) return;

        new ApexCharts(el, {
            series: [{ name: n.tipo, data: n.sparkline }],
            chart: {
                type: 'line',
                height: 35,
                width: 120,
                sparkline: { enabled: true },
                animations: { enabled: false },
            },
            stroke: { width: 2, curve: 'smooth' },
            colors: ['#0dcaf0'],
            tooltip: {
                fixed: { enabled: false },
                x: { show: false },
                y: { formatter: (v) => v + ' envíos' },
                marker: { show: false },
            },
        }).render();
    });
})();
</script>
@endpush
