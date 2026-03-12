{{-- resources/views/dashboard/partials/chart_actuaciones.blade.php --}}
{{-- Widget 3: Actuaciones por Tipo (Donut) con filtro Alpine.js --}}

<div class="card shadow-sm border-0 h-100"
     x-data="actuacionesFilter('{{ $fechaDesde }}', '{{ $fechaHasta }}')"
     @rangechange.window="loadData($event.detail.desde, $event.detail.hasta)">

    <div class="card-header bg-white border-0 pb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="fas fa-pie-chart text-success me-2"></i>
            Tipo de Actuaciones Firmadas
        </h6>
    </div>

    {{-- Filtro de rango de fechas --}}
    <div class="card-body pt-2 pb-0">
        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
            <input type="date" class="form-control form-control-sm w-auto"
                   x-model="desde"
                   @change="loadData(desde, hasta)">
            <span class="text-muted small">al</span>
            <input type="date" class="form-control form-control-sm w-auto"
                   x-model="hasta"
                   @change="loadData(desde, hasta)">
            <template x-if="loading">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
            </template>
        </div>
    </div>

    <div class="card-body pt-0">
        <div id="chart-actuaciones-tipo" style="min-height:280px;"></div>
        <div class="text-center small text-muted mt-1" x-show="!loading">
            Datos actualizados en tiempo real
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── Inicializar gráfica donut ─────────────────────────────────────────────────
let actuacionesChart;

(function () {
    const raw = window.dashboardData.actuacionesPorTipo;

    const options = {
        series: raw.cantidades,
        labels: raw.tipos,
        chart: {
            type: 'donut',
            height: 280,
            animations: { enabled: true, speed: 600 },
        },
        colors: ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6f42c1'],
        dataLabels: {
            enabled: true,
            formatter: (val) => val.toFixed(1) + '%',
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        name: { show: true },
                        value: {
                            show: true,
                            formatter: (val) => Number(val).toLocaleString('es-AR'),
                        },
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: (w) =>
                                w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                .toLocaleString('es-AR'),
                        },
                    },
                },
            },
        },
        legend: {
            position: 'bottom',
            fontSize: '12px',
        },
        tooltip: {
            y: {
                formatter: (val) => val.toLocaleString('es-AR') + ' actuaciones',
            },
        },
        responsive: [{ breakpoint: 480, options: { legend: { position: 'bottom' } } }],
    };

    actuacionesChart = new ApexCharts(
        document.querySelector('#chart-actuaciones-tipo'),
        options
    );
    actuacionesChart.render();
})();

// ── Alpine.js component ───────────────────────────────────────────────────────
function actuacionesFilter(desde, hasta) {
    return {
        desde,
        hasta,
        loading: false,

        async loadData(d, h) {
            this.desde   = d;
            this.hasta   = h;
            this.loading = true;
            try {
                const res  = await fetch(`/dashboard/actuaciones?desde=${d}&hasta=${h}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                actuacionesChart.updateOptions({
                    series: data.cantidades,
                    labels: data.tipos,
                });
            } catch (e) {
                console.error('Error cargando actuaciones:', e);
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
