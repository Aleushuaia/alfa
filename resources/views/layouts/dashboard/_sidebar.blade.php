{{--
    _sidebar.blade.php
    Partial del sidebar del layout dashboard.
    Incluir con: @include('layouts.dashboard._sidebar')
--}}
<aside id="sidebar">
    <a href="{{ route('pdf-analyzer.form') }}" class="sidebar-brand d-flex align-items-center gap-2">
        <div class="brand-icon">
            <img src="{{ asset('images/2d2.png') }}" alt="Jur-2d2" class="brand-image" style="width:100px;height:100px;object-fit:contain;">
        </div>
            <div class="brand-text" style="margin-left:20px;display:flex;flex-direction:column;justify-content:center;">
                <strong style="display:block">Jur-2d2</strong>
                <small style="display:block">Colaborador Inteligente</small>
            </div>
    </a>

    <nav class="sidebar-nav py-2">
        <p class="nav-section-label">Principal</p>

        <a href="{{ route('dashboard.v2') }}"
           class="nav-link {{ request()->routeIs('dashboard.v2') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
            Dashboard
        </a>

        <a href="{{ route('ingresados_fuero.v2') }}"
           class="nav-link {{ request()->routeIs('ingresados_fuero.v2') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-chart-pie"></i></span>
            Expedientes por Fuero
        </a>

        <p class="nav-section-label mt-2">Módulos</p>

        {{-- Expedientes --}}
        <a class="nav-link collapse-toggle"
           data-bs-toggle="collapse"
           href="#nav-expedientes"
           role="button"
           aria-expanded="{{ request()->is('expedientes*') ? 'true' : 'false' }}">
            <span class="nav-icon"><i class="fas fa-folder-open"></i></span>
            Expedientes
        </a>
        <div class="collapse {{ request()->is('expedientes*') ? 'show' : '' }}" id="nav-expedientes">
            <div class="nav-sub">
                <a href="#" class="nav-link">Listado general</a>
                <a href="#" class="nav-link">Búsqueda avanzada</a>
                <a href="#" class="nav-link">Nuevos ingresos</a>
                     <a class="nav-link collapse-toggle"
                         data-bs-toggle="collapse"
                         href="#nav-ingresados-fuero"
                         role="button"
                         aria-expanded="{{ request()->routeIs('ingresados_fuero.v2') ? 'true' : 'false' }}">
                          Ingresados por Fuero
                     </a>
                     <div class="collapse {{ request()->routeIs('ingresados_fuero.v2') ? 'show' : '' }}" id="nav-ingresados-fuero">
                          <div class="nav-sub">
                                <a href="{{ route('ingresados_fuero.v2', ['fuero' => 'civiles']) }}"
                                    class="nav-link {{ request()->query('fuero') === 'civiles' ? 'active' : '' }}">Juzgados Civiles</a>
                                <a href="{{ route('ingresados_fuero.v2', ['fuero' => 'familia']) }}"
                                    class="nav-link {{ request()->query('fuero') === 'familia' ? 'active' : '' }}">Juzgados de Familia</a>
                                <a href="{{ route('ingresados_fuero.v2', ['fuero' => 'instruccion']) }}"
                                    class="nav-link {{ request()->query('fuero') === 'instruccion' ? 'active' : '' }}">Juzgados de Instrucción</a>
                          </div>
                     </div>
            </div>
        </div>

        {{-- Actuaciones --}}
        <a class="nav-link collapse-toggle"
           data-bs-toggle="collapse"
           href="#nav-actuaciones"
           role="button"
           aria-expanded="false">
            <span class="nav-icon"><i class="fas fa-pen-nib"></i></span>
            Actuaciones
        </a>
        <div class="collapse" id="nav-actuaciones">
            <div class="nav-sub">
                <a href="#" class="nav-link">Pendientes de firma</a>
                <a href="#" class="nav-link">Firmadas</a>
            </div>
        </div>

        {{-- Escritos --}}
        <a href="#" class="nav-link">
            <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
            Escritos
        </a>

        {{-- Notificaciones --}}
        <a href="#" class="nav-link">
            <span class="nav-icon"><i class="fas fa-bell"></i></span>
            Notificaciones
        </a>

        <p class="nav-section-label mt-2">Informes</p>

        <a href="#" class="nav-link">
            <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
            Estadísticas
        </a>

        {{-- Analizador de PDF --}}
        <a href="{{ route('pdf-analyzer.form') }}"
           class="nav-link {{ request()->routeIs('pdf-analyzer.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-file-search"></i></span>
            Análisis y anonimización de PDF
        </a>
        <a href="#" class="nav-link">
            <span class="nav-icon"><i class="fas fa-file-export"></i></span>
            Exportar datos
        </a>

        <p class="nav-section-label mt-2">Sistema</p>
        <a href="#" class="nav-link">
            <span class="nav-icon"><i class="fas fa-cog"></i></span>
            Configuración
        </a>
    </nav>

    <div class="sidebar-footer">
        @if(env('DASHBOARD_DEMO', true))
            <span class="badge-demo">
                <i class="fas fa-flask"></i> Modo Demo
            </span>
        @endif
        <div class="mt-2" style="font-size:.7rem; color:rgba(255,255,255,.25);">
            SAE Kayen &copy; {{ date('Y') }}
        </div>
    </div>
</aside>

{{-- Overlay para cerrar sidebar en mobile --}}
<div id="sidebar-overlay"></div>
