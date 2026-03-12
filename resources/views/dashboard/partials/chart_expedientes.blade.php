{{-- resources/views/dashboard/partials/chart_expedientes.blade.php --}}
{{-- Widget 2: Expedientes por Organismo (barras horizontal) --}}
<div class="card shadow-sm border-0 h-100">
    <div class="card-header bg-white border-0 pb-0 d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="fas fa-building text-primary me-2"></i>
            Expedientes por Organismo
            <small class="text-muted fw-normal">(Top 10)</small>
        </h6>
        <span class="badge bg-primary-subtle text-primary">
            Mes actual vs anterior
        </span>
    </div>
    <div class="card-body pt-2">
        <div id="chart-expedientes-organismo" style="min-height:320px;"></div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const raw = window.dashboardData.expedientesPorOrganismo;

    const options = {
        series: [
            { name: 'Mes actual',   data: raw.actual },
            { name: 'Mes anterior', data: raw.anterior },
        ],
        chart: {
            type: 'bar',
            height: 320,
            toolbar: { show: false },
            animations: { enabled: true, speed: 500 },
        },
        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: '60%',
                borderRadius: 4,
                dataLabels: { position: 'top' },
            },
        },
        colors: ['#0d6efd', '#adb5bd'],
        dataLabels: { enabled: false },
        xaxis: {
            categories: raw.organismos,
            labels: { style: { fontSize: '11px' } },
        },
        yaxis: {
            labels: {
                style: { fontSize: '11px' },
                maxWidth: 180,
            },
        },
        legend: { position: 'top' },
        tooltip: {
            shared: true,
            intersect: false,
        },
        grid: { borderColor: '#f0f0f0' },
    };

    const chart = new ApexCharts(
        document.querySelector('#chart-expedientes-organismo'),
        options
    );
    chart.render();
})();
</script>
@endpush
