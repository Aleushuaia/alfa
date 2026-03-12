{{-- resources/views/dashboard/partials/chart_escritos.blade.php --}}
{{-- Widget 4: Evolución de Escritos últimos 12 meses (línea con área) --}}
<div class="card shadow-sm border-0 h-100">
    <div class="card-header bg-white border-0 pb-0 d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="fas fa-chart-line text-warning me-2"></i>
            Evolución de Escritos Ingresados
            <small class="text-muted fw-normal">(últimos 12 meses)</small>
        </h6>
    </div>
    <div class="card-body pt-2">
        <div id="chart-escritos-evolucion" style="min-height:280px;"></div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const raw = window.dashboardData.escritos;

    // Formatear etiquetas como "Ene 24", "Feb 24", etc.
    const labels = raw.labels.map(l => {
        const [y, m] = l.split('-');
        const date   = new Date(y, m - 1, 1);
        return date.toLocaleDateString('es-AR', { month: 'short', year: '2-digit' });
    });

    const options = {
        series: raw.series,
        chart: {
            type: 'area',
            height: 280,
            toolbar: { show: false },
            animations: { enabled: true, speed: 700, easing: 'easeinout' },
            zoom: { enabled: false },
        },
        colors: ['#0d6efd', '#198754', '#ffc107'],
        stroke: {
            curve: 'smooth',
            width: 2,
        },
        fill: {
            type: 'gradient',
            gradient: {
                opacityFrom: 0.4,
                opacityTo: 0.05,
            },
        },
        markers: {
            size: 4,
            hover: { size: 6 },
        },
        xaxis: {
            categories: labels,
            labels: { style: { fontSize: '11px' } },
        },
        yaxis: {
            labels: {
                formatter: (val) => val.toLocaleString('es-AR'),
                style: { fontSize: '11px' },
            },
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: { formatter: (val) => val.toLocaleString('es-AR') + ' escritos' },
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '12px',
        },
        grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
        dataLabels: { enabled: false },
    };

    const chart = new ApexCharts(
        document.querySelector('#chart-escritos-evolucion'),
        options
    );
    chart.render();
})();
</script>
@endpush
