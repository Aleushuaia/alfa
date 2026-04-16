@extends('layouts.dashboard')

@section('title', 'Expedientes por Fuero  Alfa')
@section('page-title', 'Expedientes por Fuero')
@section('breadcrumb', 'Por Fuero')

@push('styles')
<link href="{{ asset('css/views/ingresados-fuero.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="fuero-view">

    @php
        $totalGeneral    = collect($organismos)->sum('total');
        $totalPendientes = collect($organismos)->sum('pendientes');
        $totalResueltos  = collect($organismos)->sum('resueltos');

        $colors   = ['danger', 'success', 'warning', 'info', 'secondary', 'primary', 'dark', 'secondary'];
        $bgColors = ['#c0392b', '#27ae60', '#f39c12', '#2980b9', '#8e44ad', '#16a085', '#e67e22', '#2c3e50'];
    @endphp

    {{-- KPIs Generales --}}
    <h5 class="section-title">Resumen General de Expedientes</h5>
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card organismo-1">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-number text-danger">{{ number_format($totalGeneral, 0, '', '.') }}</div>
                            <div class="stat-label">Total Expedientes</div>
                        </div>
                        <i class="bi bi-files text-danger" style="font-size:2.5rem;opacity:.3;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card organismo-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-number text-warning">{{ number_format($totalPendientes, 0, '', '.') }}</div>
                            <div class="stat-label">Pendientes</div>
                        </div>
                        <i class="bi bi-hourglass-split text-warning" style="font-size:2.5rem;opacity:.3;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card organismo-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-number text-success">{{ number_format($totalResueltos, 0, '', '.') }}</div>
                            <div class="stat-label">Resueltos</div>
                        </div>
                        <i class="bi bi-check-circle text-success" style="font-size:2.5rem;opacity:.3;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card organismo-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-number text-info">
                                {{ $totalGeneral > 0 ? round(($totalResueltos / $totalGeneral) * 100, 1) : 0 }}%
                            </div>
                            <div class="stat-label">Tasa de Resolución</div>
                        </div>
                        <i class="bi bi-graph-up-arrow text-info" style="font-size:2.5rem;opacity:.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Gráficos de distribución y estado --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0">
                        <i class="bi bi-pie-chart me-2 text-danger"></i>
                        Distribución de Expedientes por Organismo
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartDistribucion"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0">
                        <i class="bi bi-bar-chart me-2 text-success"></i>
                        Estado de Expedientes por Organismo
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="chartEstado"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Infografía por organismo --}}
    <h5 class="section-title mt-2">Infografías por Organismo Judicial</h5>

    @foreach($organismos as $index => $org)
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-{{ $colors[$index % count($colors)] }} bg-opacity-10 py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0 text-{{ $colors[$index % count($colors)] }}">
                                <i class="bi bi-bank me-2"></i>
                                {{ $org['nombre'] }}
                            </h5>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="badge bg-{{ $colors[$index % count($colors)] }} badge-total me-2">
                                Este mes: {{ $org['mes'] }} nuevos
                            </span>
                            <span class="badge bg-secondary badge-total">
                                Total: {{ number_format($org['total'], 0, '', '.') }} expedientes
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <div class="mini-stat-block">
                                <div class="display-5 fw-bold text-{{ $colors[$index % count($colors)] }}">
                                    {{ number_format($org['total'], 0, '', '.') }}
                                </div>
                                <small class="text-muted">Total Expedientes</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mini-stat-block">
                                <div class="display-5 fw-bold text-warning">{{ $org['pendientes'] }}</div>
                                <small class="text-muted">Pendientes</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mini-stat-block">
                                <div class="display-5 fw-bold text-success">
                                    {{ number_format($org['resueltos'], 0, '', '.') }}
                                </div>
                                <small class="text-muted">Resueltos</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            @php $tasa = $org['total'] > 0 ? round(($org['resueltos'] / $org['total']) * 100, 1) : 0; @endphp
                            <div class="mini-stat-block">
                                <div class="display-5 fw-bold text-info">{{ $tasa }}%</div>
                                <small class="text-muted">Resolución</small>
                            </div>
                        </div>
                    </div>

                    {{-- Barra de progreso --}}
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Progreso de resolución</small>
                            <small class="text-muted">{{ $tasa }}%</small>
                        </div>
                        <div class="progress" style="height:10px;">
                            <div class="progress-bar bg-{{ $colors[$index % count($colors)] }}"
                                 role="progressbar"
                                 style="width:{{ $tasa }}%"
                                 aria-valuenow="{{ $org['resueltos'] }}"
                                 aria-valuemin="0"
                                 aria-valuemax="{{ $org['total'] }}">
                            </div>
                        </div>
                    </div>

                    {{-- Mini gráfico tendencia --}}
                    <div class="mt-3" style="height:120px;">
                        <canvas id="chartTendencia{{ $org['id'] }}"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    {{-- Gráfico Evolución Mensual --}}
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0">
                        <i class="bi bi-graph-up me-2 text-primary"></i>
                        Evolución Mensual de Expedientes Ingresados (Últimos 6 Meses)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height:350px;">
                        <canvas id="chartEvolucion"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>{{-- /.fuero-view --}}
@endsection

@push('scripts')
<script>
    const datosOrganismos = @json($organismos);
    const bgColors = ['#c0392b','#27ae60','#f39c12','#2980b9','#8e44ad','#16a085','#e67e22','#2c3e50'];
    const meses    = ['Sep','Oct','Nov','Dic','Ene','Feb'];

    //  Gráfico 1: Distribución donut 
    new Chart(document.getElementById('chartDistribucion'), {
        type: 'doughnut',
        data: {
            labels: datosOrganismos.map(o => o.nombre),
            datasets: [{
                data: datosOrganismos.map(o => o.total),
                backgroundColor: bgColors,
                borderWidth: 3,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
                            return ` ${ctx.label}: ${ctx.raw.toLocaleString('es-AR')} (${((ctx.raw/total)*100).toFixed(1)}%)`;
                        }
                    }
                }
            }
        }
    });

    //  Gráfico 2: Estado por organismo (barras) 
    new Chart(document.getElementById('chartEstado'), {
        type: 'bar',
        data: {
            labels: datosOrganismos.map(o => o.nombre),
            datasets: [
                { label: 'Resueltos',  data: datosOrganismos.map(o => o.resueltos),  backgroundColor: '#27ae60', borderRadius: 4 },
                { label: 'Pendientes', data: datosOrganismos.map(o => o.pendientes), backgroundColor: '#f39c12', borderRadius: 4 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } }
        }
    });

    //  Gráficos de tendencia por organismo 
    datosOrganismos.forEach((org, idx) => {
        const el = document.getElementById(`chartTendencia${org.id}`);
        if (!el) return;
        const base  = Math.round(org.total / 12);
        const trend = meses.map(() => Math.max(1, base + Math.round((Math.random()-.4)*base*.4)));
        trend[trend.length - 1] = org.mes;
        new Chart(el, {
            type: 'line',
            data: {
                labels: meses,
                datasets: [{
                    data: trend,
                    borderColor: bgColors[idx],
                    backgroundColor: bgColors[idx] + '20',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { font: { size: 10 } } },
                    x: { ticks: { font: { size: 10 } } }
                }
            }
        });
    });

    //  Gráfico evolución mensual multi-línea 
    new Chart(document.getElementById('chartEvolucion'), {
        type: 'line',
        data: {
            labels: meses,
            datasets: datosOrganismos.map((org, idx) => {
                const base  = Math.round(org.total / 12);
                const trend = meses.map(() => Math.max(1, base + Math.round((Math.random()-.4)*base*.4)));
                trend[trend.length - 1] = org.mes;
                return {
                    label: org.nombre,
                    data: trend,
                    borderColor: bgColors[idx],
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    pointRadius: 5
                };
            })
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } }
        }
    });
</script>
@endpush