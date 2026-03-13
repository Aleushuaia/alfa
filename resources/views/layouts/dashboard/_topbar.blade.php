{{--
    _topbar.blade.php
    Partial de la barra superior del layout dashboard.
    Incluir con: @include('layouts.dashboard._topbar')
--}}
<header id="topbar">
    <button class="topbar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
    </button>

    <div class="topbar-breadcrumb">
        <h1>@yield('page-title', 'Dashboard')</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('pdf-analyzer.form') }}"
                       class="text-decoration-none"
                       style="color:#6366f1">Inicio</a>
                </li>
                <li class="breadcrumb-item active">@yield('breadcrumb', 'Dashboard')</li>
            </ol>
        </nav>
    </div>

    <div class="topbar-actions">
        @if(isset($mes, $anio))
            <span class="period-badge">
                <i class="fas fa-calendar-alt me-1"></i>
                {{ \Carbon\Carbon::create()->month($mes)->locale('es')->isoFormat('MMMM') }} {{ $anio }}
            </span>
        @endif

        <a href="javascript:window.location.reload()" class="topbar-btn" title="Actualizar">
            <i class="fas fa-sync-alt"></i>
        </a>
        <a href="#" class="topbar-btn" title="Pantalla completa"
           onclick="toggleFullscreen(); return false;">
            <i class="fas fa-expand-alt"></i>
        </a>
    </div>
</header>
