{{--
    _sidebar.blade.php
    Partial del sidebar del layout dashboard.
    Incluir con: @include('layouts.dashboard._sidebar')
--}}
<aside id="sidebar">
    <a href="{{ route('pdf-analyzer.form') }}" class="sidebar-brand d-flex align-items-center gap-2">
        <div class="brand-icon">
            <img src="{{ asset('jur2d2.png') }}" alt="{{ config('app.name', 'Pento') }}" class="brand-image" style="width:81px;height:81px;object-fit:contain;">
        </div>
        <div class="brand-text" style="margin-left:20px;display:flex;flex-direction:column;justify-content:center;">
            <strong style="display:block">{{ config('app.name', 'Pento') }}</strong>
            <small style="display:block">Colaborador Inteligente</small>
        </div>
    </a>

    <nav class="sidebar-nav py-2">
        <p class="nav-section-label">Procesamiento de Texto</p>
        <a href="{{ route('pdf-extractor.index') }}" class="nav-link {{ request()->routeIs('pdf-extractor*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
            Pdf de imagen a texto
        </a>
        <a href="{{ route('pdf-analyzer.form') }}" class="nav-link {{ request()->routeIs('pdf-analyzer.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
            Anonimizador
        </a>
        <a href="#" class="nav-link">
            <span class="nav-icon"><i class="fas fa-list-alt"></i></span>
            Resúmenes
        </a>

        <p class="nav-section-label mt-2">Smart Tools</p>
        <a href="{{ route('transcripcion.index') }}" class="nav-link {{ request()->routeIs('transcripcion.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-microphone"></i></span>
            Transcripciones multimedia
        </a>
        <p class="nav-section-label mt-2">Insights de Gestión</p>
        <a href="#" class="nav-link">
            <span class="nav-icon"><i class="fas fa-gavel"></i></span>
            Juzgados
        </a>
        <a href="#" class="nav-link">
            <span class="nav-icon"><i class="fas fa-folder-open"></i></span>
            Expedientes
        </a>
        <a href="#" class="nav-link">
            <span class="nav-icon"><i class="fas fa-pen-nib"></i></span>
            Escritos
        </a>
        <a href="#" class="nav-link">
            <span class="nav-icon"><i class="fas fa-bell"></i></span>
            Notificaciones
        </a>
        <p class="nav-section-label mt-2">Configuración</p>
        <a href="{{ route('blacklist.index') }}" class="nav-link {{ request()->routeIs('blacklist.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-ban"></i></span>
            Gestión de la Blacklist (omitidas)
        </a>
        <a href="{{ route('whitelist.index') }}" class="nav-link {{ request()->routeIs('whitelist.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-check-circle"></i></span>
            Gestión de la Whitelist (agregadas)
        </a>
    </nav>

    <div class="sidebar-footer">
        @if(env('DASHBOARD_DEMO', true))
            <span class="badge-demo">
                <i class="fas fa-flask"></i> Modo Demo
            </span>
        @endif
        <div class="mt-2" style="font-size:.7rem; color:rgba(255,255,255,.25);">
            {{ config('app.name', 'Pento') }} &copy; {{ date('Y') }}
        </div>
    </div>
</aside>

{{-- Overlay para cerrar sidebar en mobile --}}
<div id="sidebar-overlay"></div>
