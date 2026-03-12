@extends('layouts.dashboard')

@section('title', 'Dashboard — SAE Kayen')
@section('page-title', 'Panel de Control')
@section('breadcrumb', 'Dashboard')

@push('styles')
<style>
    /* ── Filtro ───────────────────────────────────────────────────── */
    .filter-bar {
        background: #fff;
        border-radius: 14px;
        padding: 1rem 1.4rem;
        border: 1px solid rgba(0,0,0,.05);
        box-shadow: 0 2px 15px rgba(0,0,0,.05);
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }
    .filter-bar .filter-label {
        font-size: .8rem;
        font-weight: 600;
        color: #64748b;
        white-space: nowrap;
    }
    .filter-bar .form-select {
        font-size: .82rem;
        border-radius: 8px;
        border-color: #e2e8f0;
        padding: .4rem .9rem;
        max-width: 140px;
    }
    .filter-bar .btn-apply {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: .42rem 1.1rem;
        font-size: .82rem;
        font-weight: 600;
    }

    /* ── Section header ───────────────────────────────────────────── */
    .section-label {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: .85rem;
        margin-top: .25rem;
    }

    /* ── Stat cards ───────────────────────────────────────────────── */
    .stat-trend {
        font-size: .72rem;
        font-weight: 600;
        padding: .15rem .45rem;
        border-radius: 20px;
    }
    .trend-up   { background: rgba(255,255,255,.25); }
    .trend-down { background: rgba(255,255,255,.2);  }

    /* ── Actividad reciente ─────────────────────────────────────────── */
    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        padding: .8rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .activity-item:last-child { border-bottom: none; }
    .activity-dot {
        width: 34px; height: 34px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: .8rem;
        flex-shrink: 0;
        margin-top: .1rem;
    }
    .activity-dot.expediente  { background: #ede9fe; color: #7c3aed; }
    .activity-dot.actuacion   { background: #d1fae5; color: #059669; }
    .activity-dot.escrito     { background: #fef3c7; color: #d97706; }
    .activity-dot.notificacion{ background: #dbeafe; color: #2563eb; }
    .activity-text { font-size: .82rem; color: #334155; line-height: 1.4; }
    .activity-time { font-size: .72rem; color: #94a3b8; margin-top: .2rem; }

    /* ── Notificaciones tabla ─────────────────────────────────────────── */
    .notif-row { cursor: default; }
    .notif-row:hover { background: #f8fafc; }
    .notif-progress { height: 6px; border-radius: 20px; }
    .sparkline-cell canvas { display: block; }

    /* ── Chart wrappers ─────────────────────────────────────────────── */
    .chart-wrap { position: relative; }
</style>
@endpush

@section('content')

{{-- ─── Filtro de período ──────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('dashboard.v2') }}" class="filter-bar">
    <span class="filter-label"><i class="fas fa-calendar-alt me-1"></i>Período:</span>
    <select name="mes" class="form-select form-select-sm">
        @foreach(range(1,12) as $m)
            <option value="{{ $m }}" @selected($m == $mes)>
                {{ \Carbon\Carbon::create()->month($m)->locale('es')->isoFormat('MMMM') }}
            </option>
        @endforeach
    </select>
    <select name="anio" class="form-select form-select-sm">
        @foreach(range(now()->year - 2, now()->year) as $y)
            <option value="{{ $y }}" @selected($y == $anio)>{{ $y }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-apply btn-sm">
        <i class="fas fa-filter me-1"></i>Aplicar
    </button>
    <a href="{{ route('ingresados_fuero.v2') }}" class="ms-auto btn btn-sm btn-outline-secondary rounded-3"
       style="font-size:.8rem;">
        <i class="fas fa-chart-pie me-1"></i>Vista por Fuero
    </a>
</form>

{{-- ─── Cards KPI ──────────────────────────────────────────────────────── --}}
<p class="section-label">Indicadores del período</p>
<div class="row g-3 mb-4">
    @php
        $statConfig = [
            ['class' => 'k-stat-card-primary', 'icon' => 'fas fa-folder-open', 'key_v' => 'valor', 'key_a' => 'anterior'],
            ['class' => 'k-stat-card-success', 'icon' => 'fas fa-pen-nib',     'key_v' => 'valor', 'key_a' => 'anterior'],
            ['class' => 'k-stat-card-warning', 'icon' => 'fas fa-file-alt',    'key_v' => 'valor', 'key_a' => 'anterior'],
            ['class' => 'k-stat-card-info',    'icon' => 'fas fa-bell',        'key_v' => 'valor', 'key_a' => 'anterior'],
        ];
    @endphp
    @foreach($cards as $i => $card)
    @php
        $cfg   = $statConfig[$i] ?? $statConfig[0];
        $delta = $card['valor'] - $card['anterior'];
        $pct   = $card['anterior'] > 0 ? round(abs($delta / $card['anterior']) * 100, 1) : 0;
        $up    = $delta >= 0;
    @endphp
    <div class="col-sm-6 col-xl-3">
        <div class="k-stat-card {{ $cfg['class'] }}">
            <div class="stat-icon">
                <i class="{{ $cfg['icon'] }}"></i>
            </div>
            <div class="stat-value" data-countup="{{ $card['valor'] }}">{{ number_format($card['valor'], 0, ',', '.') }}</div>
            <div class="stat-label">{{ $card['titulo'] }}</div>
            <div class="stat-delta">
                <span class="stat-trend {{ $up ? 'trend-up' : 'trend-down' }}">
                    <i class="fas fa-arrow-{{ $up ? 'up' : 'down' }}"></i>
                    {{ $pct }}%
                </span>
                vs mes anterior ({{ number_format($card['anterior'], 0, ',', '.') }})
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ─── Fila 2: Expedientes por organismo + Actuaciones por tipo ─────── --}}
<div class="row g-3 mb-3">
    {{-- Expedientes por organismo --}}
    <div class="col-lg-7">
        <div class="k-card h-100">
            <div class="k-card-header">
                <span class="k-card-icon" style="background:#ede9fe; color:#7c3aed;">
                    <i class="fas fa-building"></i>
                </span>
                <h2 class="k-card-title">Expedientes por Organismo</h2>
                <span class="ms-auto badge rounded-pill" style="background:#ede9fe;color:#7c3aed;font-size:.7rem;">
                    Top 10 — {{ \Carbon\Carbon::create()->month($mes)->locale('es')->isoFormat('MMMM') }}
                </span>
            </div>
            <div class="k-card-body">
                <div class="chart-wrap" style="height:280px;">
                    <canvas id="chartOrganismos"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Actuaciones por tipo --}}
    <div class="col-lg-5">
        <div class="k-card h-100">
            <div class="k-card-header">
                <span class="k-card-icon" style="background:#d1fae5;color:#059669;">
                    <i class="fas fa-pen-nib"></i>
                </span>
                <h2 class="k-card-title">Actuaciones por Tipo</h2>
            </div>
            <div class="k-card-body">
                {{-- Filtro rango fechas --}}
                <form id="formActuaciones" class="d-flex gap-2 mb-3 align-items-center flex-wrap">
                    <input type="date" class="form-control form-control-sm rounded-3"
                           id="inputDesde" name="desde" value="{{ $fechaDesde }}" style="max-width:145px;">
                    <span style="font-size:.8rem;color:#94a3b8;">→</span>
                    <input type="date" class="form-control form-control-sm rounded-3"
                           id="inputHasta" name="hasta" value="{{ $fechaHasta }}" style="max-width:145px;">
                    <button type="submit" class="btn btn-sm rounded-3"
                            style="background:#6366f1;color:#fff;font-size:.78rem;">Filtrar</button>
                </form>
                <div class="chart-wrap" style="height:230px;">
                    <canvas id="chartActuaciones"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── Fila 3: Escritos evolución + Actividad reciente ──────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="k-card h-100">
            <div class="k-card-header">
                <span class="k-card-icon" style="background:#fef3c7;color:#d97706;">
                    <i class="fas fa-file-alt"></i>
                </span>
                <h2 class="k-card-title">Evolución de Escritos — Últimos 12 meses</h2>
            </div>
            <div class="k-card-body">
                <div class="chart-wrap" style="height:270px;">
                    <canvas id="chartEscritos"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="k-card h-100">
            <div class="k-card-header">
                <span class="k-card-icon" style="background:#dbeafe;color:#2563eb;">
                    <i class="fas fa-history"></i>
                </span>
                <h2 class="k-card-title">Actividad Reciente</h2>
            </div>
            <div class="k-card-body" style="overflow-y:auto;max-height:310px;">
                @foreach($actividadReciente as $item)
                @php
                    $tipoMap = [
                        'expediente'   => ['icon' => 'fas fa-folder-open', 'class' => 'expediente'],
                        'actuacion'    => ['icon' => 'fas fa-pen-nib',     'class' => 'actuacion'],
                        'escrito'      => ['icon' => 'fas fa-file-alt',    'class' => 'escrito'],
                        'notificacion' => ['icon' => 'fas fa-bell',        'class' => 'notificacion'],
                    ];
                    $tm = $tipoMap[$item->tipo] ?? ['icon'=>'fas fa-circle','class'=>'expediente'];
                @endphp
                <div class="activity-item">
                    <span class="activity-dot {{ $tm['class'] }}">
                        <i class="{{ $tm['icon'] }}"></i>
                    </span>
                    <div>
                        <div class="activity-text">{{ $item->descripcion }}</div>
                        <div class="activity-time">
                            <i class="far fa-clock me-1"></i>
                            {{ \Carbon\Carbon::parse($item->fecha)->diffForHumans() }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ─── Fila 4: Notificaciones ─────────────────────────────────────────── --}}
<div class="row g-3">
    <div class="col-12">
        <div class="k-card">
            <div class="k-card-header">
                <span class="k-card-icon" style="background:#dbeafe;color:#2563eb;">
                    <i class="fas fa-bell"></i>
                </span>
                <h2 class="k-card-title">Notificaciones del período</h2>
                <span class="ms-auto badge rounded-pill" style="background:#dbeafe;color:#2563eb;font-size:.7rem;">
                    {{ collect($notificaciones)->sum('cantidad') }} total
                </span>
            </div>
            <div class="k-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:.83rem;">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th class="px-4 py-3 fw-600 text-muted border-0">Tipo</th>
                                <th class="py-3 fw-600 text-muted border-0 text-end">Cantidad</th>
                                <th class="py-3 fw-600 text-muted border-0" style="min-width:180px;">Distribución</th>
                                <th class="py-3 fw-600 text-muted border-0 text-center">Tendencia (6m)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notificaciones as $i => $n)
                            <tr class="notif-row">
                                <td class="px-4 py-3 border-0">
                                    <span class="fw-600">{{ $n['tipo'] }}</span>
                                </td>
                                <td class="py-3 border-0 text-end fw-700" style="color:#0f172a;">
                                    {{ number_format($n['cantidad'], 0, ',', '.') }}
                                </td>
                                <td class="py-3 border-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1 notif-progress">
                                            <div class="progress-bar"
                                                 style="width:{{ $n['porcentaje'] }}%;background:linear-gradient(90deg,#6366f1,#8b5cf6);">
                                            </div>
                                        </div>
                                        <small class="text-muted" style="min-width:38px;">{{ $n['porcentaje'] }}%</small>
                                    </div>
                                </td>
                                <td class="py-3 border-0 text-center">
                                    <canvas id="sparkline-{{ $i }}" width="90" height="28"
                                            data-values="{{ implode(',', $n['sparkline']) }}"></canvas>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const D = window.dashboardData = {
    expedientesPorOrganismo: @json($expedientesPorOrganismo),
    actuacionesPorTipo:      @json($actuacionesPorTipo),
    escritos:                @json($escritos),
    notificaciones:          @json($notificaciones),
    desde: '{{ $fechaDesde }}',
    hasta: '{{ $fechaHasta }}',
    actuacionesUrl: '{{ route("dashboard.actuaciones") }}',
};

/* Palette */
const PALETTE = ['#6366f1','#8b5cf6','#06b6d4','#10b981','#f59e0b','#ef4444','#ec4899','#14b8a6','#f97316','#84cc16'];

/* ─── Chart 1: Organismos (horizontal bar) ─────────── */
new Chart(document.getElementById('chartOrganismos'), {
    type: 'bar',
    data: {
        labels: D.expedientesPorOrganismo.organismos ?? [],
        datasets: [
            {
                label: 'Mes actual',
                data: D.expedientesPorOrganismo.actual ?? [],
                backgroundColor: '#6366f1',
                borderRadius: 6,
                borderSkipped: false,
            },
            {
                label: 'Mes anterior',
                data: D.expedientesPorOrganismo.anterior ?? [],
                backgroundColor: 'rgba(99,102,241,.2)',
                borderRadius: 6,
                borderSkipped: false,
            }
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: { beginAtZero: true, ticks: { precision: 0 } },
            y: { ticks: { font: { size: 11 } } }
        },
        plugins: { legend: { position: 'bottom' } }
    }
});

/* ─── Chart 2: Actuaciones donut ───────────────────── */
let chartActuaciones = new Chart(document.getElementById('chartActuaciones'), {
    type: 'doughnut',
    data: {
        labels: D.actuacionesPorTipo.tipos ?? [],
        datasets: [{
            data: D.actuacionesPorTipo.cantidades ?? [],
            backgroundColor: PALETTE,
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 8,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: ctx => {
                        const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                        return ` ${ctx.label}: ${ctx.raw.toLocaleString('es-AR')} (${((ctx.raw/total)*100).toFixed(1)}%)`;
                    }
                }
            }
        }
    }
});

/* AJAX filtro actuaciones */
document.getElementById('formActuaciones').addEventListener('submit', async e => {
    e.preventDefault();
    const params = new URLSearchParams({ desde: document.getElementById('inputDesde').value, hasta: document.getElementById('inputHasta').value });
    const res    = await fetch(`${D.actuacionesUrl}?${params}`);
    const data   = await res.json();
    chartActuaciones.data.labels   = data.tipos ?? [];
    chartActuaciones.data.datasets[0].data = data.cantidades ?? [];
    chartActuaciones.update();
});

/* ─── Chart 3: Escritos (area multi-line) ──────────── */
const escritosLabels = D.escritos.labels ?? [];
const escritosSeries = (D.escritos.series ?? []).map((s, i) => ({
    label: s.name,
    data: s.data,
    borderColor: PALETTE[i],
    backgroundColor: PALETTE[i] + '18',
    fill: true,
    tension: 0.4,
    pointRadius: 3,
    pointHoverRadius: 6,
}));

new Chart(document.getElementById('chartEscritos'), {
    type: 'line',
    data: { labels: escritosLabels, datasets: escritosSeries },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        },
        plugins: { legend: { position: 'bottom' } }
    }
});

/* ─── Sparklines de notificaciones ─────────────────── */
document.querySelectorAll('canvas[data-values]').forEach(canvas => {
    const values = canvas.dataset.values.split(',').map(Number);
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: values.map((_,i) => i),
            datasets: [{
                data: values,
                borderColor: '#6366f1',
                borderWidth: 2,
                pointRadius: 0,
                fill: false,
                tension: 0.4,
            }]
        },
        options: {
            animation: false,
            responsive: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: { x: { display: false }, y: { display: false } }
        }
    });
});
</script>
@endpush
