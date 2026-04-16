{{--
    _sidebar.blade.php
    Partial del sidebar del layout dashboard.
    Incluir con: @include('layouts.dashboard._sidebar')
--}}
<aside id="sidebar">
    <a href="{{ route('pdf-analyzer.form') }}" class="sidebar-brand d-flex align-items-center gap-2">
        <div class="brand-icon">
            <img src="{{ asset('alfa.png') }}" alt="{{ config('app.name', 'Alfa colaborador inteligente') }}" class="brand-image">
        </div>
        <div class="brand-text" style="margin-left:20px;display:flex;flex-direction:column;justify-content:center;">
            <strong style="display:block;font-size:1.15rem">Alfa</strong>
            <small style="display:block;font-size:.82rem;opacity:.7">colaborador inteligente</small>
        </div>
    </a>

    <nav class="sidebar-nav py-2">
        {{-- ── Procesamiento de Texto ── visible para todos los autenticados --}}
        <p class="nav-section-label">Procesamiento de Texto</p>
        <a href="{{ route('pdf-extractor.index') }}" class="nav-link {{ request()->routeIs('pdf-extractor*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
            Pdf de imagen a texto
        </a>
        <a href="{{ route('pdf-analyzer.form') }}" class="nav-link {{ request()->routeIs('pdf-analyzer.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
            Anonimizador
        </a>

        {{-- ── Smart Tools ── visible para todos los autenticados --}}
        <p class="nav-section-label mt-2">Smart Tools</p>
        <a href="{{ route('transcripcion.index') }}" class="nav-link {{ request()->routeIs('transcripcion.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-microphone"></i></span>
            Transcripciones
        </a>

        {{-- ── Configuración ── visible para todos los autenticados --}}
        <p class="nav-section-label mt-2">Configuración</p>
        <a href="{{ route('blacklist.index') }}" class="nav-link {{ request()->routeIs('blacklist.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-ban"></i></span>
            Gestión de la Blacklist (omitidas)
        </a>
        <a href="{{ route('whitelist.index') }}" class="nav-link {{ request()->routeIs('whitelist.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-check-circle"></i></span>
            Gestión de la Whitelist (agregadas)
        </a>
        <a href="{{ route('entity-config.index') }}" class="nav-link {{ request()->routeIs('entity-config.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-palette"></i></span>
            Gestión de entidades
        </a>
        <a href="{{ route('theme-config.index') }}" class="nav-link {{ request()->routeIs('theme-config.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-swatchbook"></i></span>
            Colores del tema
        </a>

        {{-- ── Ajustes ── visible solo para administradores --}}
        @if(auth()->check() && auth()->user()->hasRole('administrador'))
        <p class="nav-section-label mt-2">Ajustes</p>
        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
            Gestión de usuarios y roles
        </a>
        @endif
    </nav>

    <div class="sidebar-footer">
        @if(auth()->check())
            <div class="d-flex align-items-center gap-2 mb-2" style="font-size:.78rem;color:rgba(255,255,255,.6)">
                <i class="fas fa-user-circle"></i>
                <span>{{ auth()->user()->name }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-sm w-100" style="background:rgba(255,255,255,.06);color:rgba(255,255,255,.6);border:1px solid rgba(255,255,255,.08);border-radius:8px;font-size:.75rem;padding:.35rem .5rem;">
                    <i class="fas fa-sign-out-alt me-1"></i>Cerrar sesión
                </button>
            </form>
        @endif
        <div class="mt-2" style="font-size:.7rem; color:rgba(255,255,255,.25);">
            Alfa &copy; {{ date('Y') }}
        </div>
    </div>
</aside>

{{-- Overlay para cerrar sidebar en mobile --}}
<div id="sidebar-overlay"></div>
