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
        <p class="nav-section-label">Principal</p>

        <a href="{{ route('dashboard.v2') }}" class="nav-link {{ request()->routeIs('dashboard.v2') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
            Main Dashboard
        </a>

        <p class="nav-section-label mt-2">Procesamiento de Texto</p>
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
        <a href="#" class="nav-link">
            <span class="nav-icon"><i class="fas fa-file-pdf"></i></span>
            Unir o separar PDF
        </a>
        <a href="#" class="nav-link">
            <span class="nav-icon"><i class="fas fa-book"></i></span>
            PDFs modo libro
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
        <a href="#" class="nav-link">
            <span class="nav-icon"><i class="fas fa-envelope"></i></span>
            Comunicaciones Digitales
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
