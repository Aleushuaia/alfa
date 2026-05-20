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
        <a href="{{ route('pdf-extractor.index') }}"
           class="nav-link {{ request()->routeIs('pdf-extractor*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="PDF de imagen a texto">
            <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
            <span>Pdf de imagen a texto</span>
        </a>
        <a href="{{ route('word-anonymizer.index') }}"
           class="nav-link {{ request()->routeIs('word-anonymizer*') ? 'active' : '' }}"
           target="_blank"
           title=""
           data-sidebar-tooltip="Anonimizador">
            <span class="nav-icon"><i class="fas fa-file-word"></i></span>
            <span>Anonimizador</span>
        </a>

        {{-- ── Smart Tools ── visible para todos los autenticados --}}
        <p class="nav-section-label mt-2">Smart Tools</p>
        <a href="{{ route('transcripcion.index') }}"
           class="nav-link {{ request()->routeIs('transcripcion.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Transcripciones">
            <span class="nav-icon"><i class="fas fa-microphone"></i></span>
            <span>Transcripciones</span>
        </a>
        <a href="{{ route('ollama.test') }}"
           class="nav-link {{ request()->routeIs('ollama.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Probar modelo LLM">
            <span class="nav-icon"><i class="fas fa-robot"></i></span>
            <span>Probar modelo de IA</span>
        </a>
        <a href="{{ route('sujetos-procesales.index') }}"
           class="nav-link {{ request()->routeIs('sujetos-procesales.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Extraer sujetos procesales">
            <span class="nav-icon"><i class="fas fa-users"></i></span>
            <span>Extracción con IA</span>
        </a>

        {{-- ── Configuración ── visible para todos los autenticados --}}
        <p class="nav-section-label mt-2">Configuración</p>
        <a href="{{ route('blacklist.index') }}"
           class="nav-link {{ request()->routeIs('blacklist.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Blacklist (omitidas)">
            <span class="nav-icon"><i class="fas fa-ban"></i></span>
            <span>Gestión de la Blacklist (omitidas)</span>
        </a>
        <a href="{{ route('whitelist.index') }}"
           class="nav-link {{ request()->routeIs('whitelist.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Whitelist (agregadas)">
            <span class="nav-icon"><i class="fas fa-check-circle"></i></span>
            <span>Gestión de la Whitelist (agregadas)</span>
        </a>
        <a href="{{ route('entity-config.index') }}"
           class="nav-link {{ request()->routeIs('entity-config.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Colores de Entidades">
            <span class="nav-icon"><i class="fas fa-palette"></i></span>
            <span>Colores de Entidades</span>
        </a>
        <a href="{{ route('theme-config.index') }}"
           class="nav-link {{ request()->routeIs('theme-config.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Colores del tema">
            <span class="nav-icon"><i class="fas fa-swatchbook"></i></span>
            <span>Colores del tema</span>
        </a>

        {{-- ── Ajustes ── visible solo para administradores --}}
        @if(auth()->check() && auth()->user()->hasRole('administrador'))
        <p class="nav-section-label mt-2">Ajustes</p>
        <a href="{{ route('admin.users.index') }}"
           class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Gestión de usuarios y roles">
            <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
            <span>Gestión de usuarios y roles</span>
        </a>
        <a href="{{ route('admin.unidades.index') }}"
           class="nav-link {{ request()->routeIs('admin.unidades.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Unidades de Trabajo">
            <span class="nav-icon"><i class="fas fa-sitemap"></i></span>
            <span>Unidades de Trabajo</span>
        </a>
        <a href="{{ route('admin.administradores-unidades.index') }}"
           class="nav-link {{ request()->routeIs('admin.administradores-unidades.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Administradores de Unidades">
            <span class="nav-icon"><i class="fas fa-user-shield"></i></span>
            <span>Administradores de Unidades</span>
        </a>
        <a href="{{ route('admin.prompts.index') }}"
           class="nav-link {{ request()->routeIs('admin.prompts.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Gestión de Prompts">
            <span class="nav-icon"><i class="fas fa-file-code"></i></span>
            <span>Gestión de Prompts</span>
        </a>
        @endif

        {{-- ── Gestionar Unidad ── visible para administradores de unidad --}}
        @if(auth()->check() && !auth()->user()->hasRole('administrador') && auth()->user()->unidadesAdministradas()->exists())
        <p class="nav-section-label mt-2">Mi Unidad</p>
        <a href="{{ route('gestionar-unidad.index') }}"
           class="nav-link {{ request()->routeIs('gestionar-unidad.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Gestionar mi Unidad">
            <span class="nav-icon"><i class="fas fa-building-user"></i></span>
            <span>Gestionar Unidad</span>
        </a>
        @endif

        {{-- ── Panel ── --}}
        <p class="nav-section-label mt-2">Panel</p>
        <button type="button"
                id="btn-collapse"
                class="sidebar-collapse-btn"
                data-sidebar-tooltip="Minimizar panel de menú"
                title="Minimizar panel de menú">
            <span class="nav-icon"><i class="fas fa-angles-left" id="collapse-icon"></i></span>
        </button>
    </nav>

    <div class="sidebar-footer">
        @if(auth()->check())
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="sidebar-logout-link"
                        data-sidebar-tooltip="Cerrar sesión"
                        title="">
                    <i class="fas fa-power-off"></i>
                    <span class="collapse-label">Cerrar sesión</span>
                </button>
            </form>
        @endif
        <div class="sidebar-copy collapse-label">
            Alfa &copy; {{ date('Y') }}
        </div>
    </div>
</aside>

{{-- Overlay para cerrar sidebar en mobile --}}
<div id="sidebar-overlay"></div>
