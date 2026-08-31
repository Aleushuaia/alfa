{{--
    _sidebar.blade.php
    Partial del sidebar del layout dashboard.
    Incluir con: @include('layouts.dashboard._sidebar')
--}}
@php
    $isAdmin = auth()->check() && auth()->user()->hasRole('administrador');
@endphp
<aside id="sidebar">
    <a href="{{ route('pdf-analyzer.form') }}" class="sidebar-brand d-flex align-items-center gap-2">
        <div class="brand-icon">
            <img src="{{ alfa_asset('alfa.png') }}" alt="{{ config('app.name', 'Alfa colaborador inteligente') }}" class="brand-image">
        </div>
        <div class="brand-text" style="margin-left:20px;display:flex;flex-direction:column;justify-content:center;">
            <strong style="display:block;font-size:1.15rem">Alfa</strong>
            <small style="display:block;font-size:.82rem;opacity:.7">colaborador inteligente</small>
        </div>
    </a>

    <nav class="sidebar-nav py-2">

        {{-- ── Procesamiento de Texto ────────────────────────────────────── --}}
        @canany(['menu.pdf-extractor', 'menu.word-anonymizer', 'menu.herramientas_pdf'])
        <p class="nav-section-label">Procesamiento de Texto</p>
        @can('menu.pdf-extractor')
        <a href="{{ route('pdf-extractor.index') }}"
           class="nav-link {{ request()->routeIs('pdf-extractor*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="PDF de imagen a texto">
            <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
            <span>Pdf de imagen a texto</span>
        </a>
        @endcan
        @can('menu.word-anonymizer')
        <a href="{{ route('word-anonymizer.index') }}"
           class="nav-link {{ request()->routeIs('word-anonymizer*') ? 'active' : '' }}"
           target="_blank" rel="noopener noreferrer"
           title=""
           data-sidebar-tooltip="Anonimizador">
            <span class="nav-icon"><i class="fas fa-file-word"></i></span>
            <span>Anonimizador</span>
        </a>
        @endcan
        @can('menu.herramientas_pdf')
        <a href="{{ route('pdf-tools.index') }}"
           class="nav-link {{ request()->routeIs('pdf-tools.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Herramientas PDF">
            <span class="nav-icon"><i class="fas fa-file-pdf"></i></span>
            <span>Herramientas PDF</span>
        </a>
        @endcan
        @endcanany

        {{-- ── Smart Tools ───────────────────────────────────────────────── --}}
        @canany(['menu.transcripcion', 'menu.ollama', 'menu.sujetos-procesales'])
        <p class="nav-section-label mt-2">Smart Tools</p>
        @can('menu.transcripcion')
        <a href="{{ route('transcripcion.index') }}"
           class="nav-link {{ request()->routeIs('transcripcion.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Transcripciones">
            <span class="nav-icon"><i class="fas fa-microphone"></i></span>
            <span>Transcripciones</span>
        </a>
        @endcan
        @can('menu.ollama')
        <a href="{{ route('ollama.test') }}"
           class="nav-link {{ request()->routeIs('ollama.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Probar modelo LLM">
            <span class="nav-icon"><i class="fas fa-robot"></i></span>
            <span>Probar modelo de IA</span>
        </a>
        @endcan
        @can('menu.sujetos-procesales')
        <a href="{{ route('sujetos-procesales.index') }}"
           class="nav-link {{ request()->routeIs('sujetos-procesales.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Extraer sujetos procesales">
            <span class="nav-icon"><i class="fas fa-users"></i></span>
            <span>Extracción con IA</span>
        </a>
        @endcan
        @endcanany

        {{-- ── Configuración ─────────────────────────────────────────────── --}}
        @canany(['menu.blacklist', 'menu.whitelist', 'menu.entity-config', 'menu.theme-config'])
        <p class="nav-section-label mt-2">Configuración</p>
        @can('menu.blacklist')
        <a href="{{ route('blacklist.index') }}"
           class="nav-link {{ request()->routeIs('blacklist.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Blacklist (omitidas)">
            <span class="nav-icon"><i class="fas fa-ban"></i></span>
            <span>Gestión de la Blacklist (omitidas)</span>
        </a>
        @endcan
        @can('menu.whitelist')
        <a href="{{ route('whitelist.index') }}"
           class="nav-link {{ request()->routeIs('whitelist.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Whitelist (agregadas)">
            <span class="nav-icon"><i class="fas fa-check-circle"></i></span>
            <span>Gestión de la Whitelist (agregadas)</span>
        </a>
        @endcan
        @can('menu.entity-config')
        <a href="{{ route('entity-config.index') }}"
           class="nav-link {{ request()->routeIs('entity-config.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Colores de Entidades">
            <span class="nav-icon"><i class="fas fa-palette"></i></span>
            <span>Colores de Entidades</span>
        </a>
        @endcan
        @can('menu.theme-config')
        <a href="{{ route('theme-config.index') }}"
           class="nav-link {{ request()->routeIs('theme-config.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Colores del tema">
            <span class="nav-icon"><i class="fas fa-swatchbook"></i></span>
            <span>Colores del tema</span>
        </a>
        @endcan
        @endcanany

        {{-- ── Ajustes ── visible solo para administradores ─────────────── --}}
        @if($isAdmin)
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
        <a href="{{ route('admin.menu-permissions.index') }}"
           class="nav-link {{ request()->routeIs('admin.menu-permissions.*') ? 'active' : '' }}"
           title=""
           data-sidebar-tooltip="Permisos de Menú">
            <span class="nav-icon"><i class="fas fa-shield-halved"></i></span>
            <span>Permisos de Menú</span>
        </a>
        @endif

        {{-- ── Gestionar Unidad ── visible para administradores de unidad ── --}}
        @if(auth()->check() && !$isAdmin && auth()->user()->unidadesAdministradas()->exists())
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
