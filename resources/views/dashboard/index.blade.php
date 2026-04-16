@extends('layouts.dashboard')

@section('title', 'Dashboard — Alfa')
@section('page-title', 'Dashboard')
@section('breadcrumb', 'Dashboard')
@section('header-title', 'Alfa — Panel de Control')

@push('styles')
<style>
    .apexcharts-canvas { width: 100% !important; }
</style>
@endpush

@section('content')

{{-- ─── Filtro de período ──────────────────────────────────────────────────── --}}
<div class="row mb-3">
    <div class="col-12">
        <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="form-label mb-0 fw-semibold">Período:</label>
            <select name="mes" class="form-select form-select-sm w-auto">
                @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" @selected($m == $mes)>
                        {{ \Carbon\Carbon::create()->month($m)->locale('es')->monthName }}
                    </option>
                @endforeach
            </select>
            <select name="anio" class="form-select form-select-sm w-auto">
                @foreach(range(now()->year - 2, now()->year) as $y)
                    <option value="{{ $y }}" @selected($y == $anio)>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-filter me-1"></i> Aplicar
            </button>
        </form>
    </div>
</div>

{{-- ─── Widget 1: Cards resumen ───────────────────────────────────────────── --}}
@include('dashboard.partials.cards', ['cards' => $cards])

{{-- Mostrar total expedientes ingresados (destacado) --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-light border rounded d-flex align-items-center">
            <i class="fas fa-folder-open fa-2x text-primary me-3"></i>
            <div>
                <div class="small text-muted">Expedientes ingresados (periodo seleccionado)</div>
                <div class="h4 mb-0">{{ number_format($expedientesIngresados) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ─── Fila 2: Organismos + Actuaciones ─────────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-lg-7">
        @include('dashboard.partials.chart_expedientes', ['data' => $expedientesPorOrganismo])
    </div>
    <div class="col-lg-5">
        @include('dashboard.partials.chart_actuaciones', [
            'data'       => $actuacionesPorTipo,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
        ])
    </div>
</div>

{{-- ─── Fila 3: Escritos + Actividad reciente ─────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        @include('dashboard.partials.chart_escritos', ['data' => $escritos])
    </div>
    <div class="col-lg-4">
        @include('dashboard.partials.actividad_reciente', ['items' => $actividadReciente])
    </div>
</div>

{{-- ─── Widget 5: Notificaciones ──────────────────────────────────────────── --}}
<div class="row g-3">
    <div class="col-12">
        @include('dashboard.partials.tabla_notificaciones', ['notificaciones' => $notificaciones])
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Datos globales para los partials
    window.dashboardData = {
        expedientesPorOrganismo: @json($expedientesPorOrganismo),
        actuacionesPorTipo:      @json($actuacionesPorTipo),
        escritos:                @json($escritos),
        notificaciones:          @json($notificaciones),
        expedientesIngresados:   @json($expedientesIngresados),
    };
</script>
@endpush
