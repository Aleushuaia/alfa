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
                       style="color:var(--accent)">Inicio</a>
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
        <div class="topbar-theme-toggle">
            <label class="topbar-theme-switch">
                <input type="checkbox" id="darkModeSwitch">
                <span class="topbar-theme-slider"></span>
            </label>
            <span class="topbar-theme-label">Dark</span>
        </div>

        @auth
        <div class="d-flex align-items-center gap-2 ms-3" style="font-size:.82rem;color:var(--topbar-color)">
            <i class="fas fa-user-circle" style="color:var(--accent);font-size:1.1rem"></i>
            <span class="d-none d-md-inline fw-semibold">{{ auth()->user()->name }}</span>
        </div>
        @endauth
    </div>
</header>
