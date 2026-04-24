{{--
    _topbar.blade.php
    Partial de la barra superior del layout dashboard.
    Incluir con: @include('layouts.dashboard._topbar')
--}}

{{-- ── Modal: Cambiar contraseña propia ─────────────────────────────────────── --}}
<div class="modal fade" id="modalCambiarPassword" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px">
            <form method="POST" action="{{ route('profile.change-password') }}">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--card-border);padding:1rem 1.25rem .75rem">
                    <h5 class="modal-title" style="color:var(--heading-color);font-size:.95rem;font-weight:700">
                        <i class="fas fa-key me-2" style="color:var(--accent)"></i>Cambiar contraseña
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:var(--btn-close-filter,none)"></button>
                </div>
                <div class="modal-body" style="padding:1.25rem">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" style="color:var(--body-color);opacity:.75;text-transform:uppercase;letter-spacing:.4px;font-size:.68rem">Nueva contraseña</label>
                        <input type="password" name="password" class="form-control" required minlength="8" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold" style="color:var(--body-color);opacity:.75;text-transform:uppercase;letter-spacing:.4px;font-size:.68rem">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8" placeholder="Repetir contraseña" autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);padding:.75rem 1.25rem">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius:8px">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary" style="background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:8px;font-weight:600">
                        <i class="fas fa-save me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
        {{-- ── Selector de unidad activa ─────────────────────────────── --}}
        @php
            $__authUser      = auth()->user();
            $__unidadActiva  = app(\App\Services\UnidadActivaService::class)->get($__authUser);
            $__misUnidades   = $__authUser->unidades()->orderBy('descripcion')->get();
        @endphp

        @if($__unidadActiva)
            @if($__misUnidades->count() > 1)
                <div class="dropdown" id="unidad-selector-wrap">
                    <button class="btn btn-sm dropdown-toggle d-flex align-items-center gap-1"
                            id="unidadDropdown"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            style="background:var(--accent);color:#fff;border:none;border-radius:8px;font-size:.78rem;padding:.28rem .65rem;max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                            title="{{ $__unidadActiva->descripcion }}">
                        <i class="fas fa-sitemap" style="flex-shrink:0"></i>
                        <span style="overflow:hidden;text-overflow:ellipsis;max-width:170px;display:inline-block;">
                            {{ $__unidadActiva->descripcion }}
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm"
                        aria-labelledby="unidadDropdown"
                        style="min-width:260px;font-size:.82rem;border-radius:12px;border:1px solid var(--card-border);background:var(--card-bg);padding:.4rem .3rem;">
                        <li class="px-3 py-1" style="color:var(--body-color);opacity:.55;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">
                            Cambiar unidad de trabajo
                        </li>
                        @foreach($__misUnidades as $__u)
                        <li>
                            <form method="POST" action="{{ route('switch-unidad') }}" class="mb-0">
                                @csrf
                                <input type="hidden" name="unidad_id" value="{{ $__u->id }}">
                                <button type="submit"
                                        class="dropdown-item d-flex align-items-center gap-2"
                                        style="border-radius:8px;color:var(--body-color);{{ $__u->id === $__unidadActiva->id ? 'font-weight:600;color:var(--accent);' : '' }}">
                                    @if($__u->id === $__unidadActiva->id)
                                        <i class="fas fa-check-circle text-success" style="width:14px"></i>
                                    @else
                                        <i class="fas fa-sitemap" style="width:14px;opacity:.4"></i>
                                    @endif
                                    {{ $__u->descripcion }}
                                </button>
                            </form>
                        </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <span class="d-flex align-items-center gap-1"
                      style="background:var(--accent);color:#fff;border-radius:8px;font-size:.78rem;padding:.28rem .65rem;max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                      title="{{ $__unidadActiva->descripcion }}">
                    <i class="fas fa-sitemap" style="flex-shrink:0"></i>
                    <span style="overflow:hidden;text-overflow:ellipsis;max-width:170px;display:inline-block;">
                        {{ $__unidadActiva->descripcion }}
                    </span>
                </span>
            @endif
        @endif

        {{-- ── Perfil del usuario ────────────────────────────────────── --}}
        <div class="dropdown ms-2" id="profile-dropdown-wrap">
            <button type="button"
                    class="d-flex align-items-center gap-2 topbar-btn"
                    id="profileDropdown"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                    style="border:1px solid var(--card-border);border-radius:10px;padding:.28rem .65rem;font-size:.82rem;color:var(--topbar-color);background:transparent;cursor:pointer;gap:.4rem">
                <i class="fas fa-user-circle" style="color:var(--accent);font-size:1.1rem"></i>
                <span class="d-none d-md-inline fw-semibold">Perfil</span>
                <i class="fas fa-chevron-down" style="font-size:.6rem;opacity:.5"></i>
            </button>

            <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg"
                 aria-labelledby="profileDropdown"
                 style="min-width:290px;border-radius:16px;border:1px solid var(--card-border);background:var(--card-bg);overflow:hidden;">

                {{-- Header del perfil --}}
                <div class="p-3 pb-2" style="background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:46px;height:46px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="fas fa-user-circle" style="font-size:1.6rem;color:#fff"></i>
                        </div>
                        <div style="overflow:hidden">
                            <div class="fw-bold" style="font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                {{ auth()->user()->name }}
                            </div>
                            <div style="font-size:.75rem;opacity:.85;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                {{ auth()->user()->email }}
                            </div>
                            <div class="mt-1 d-flex flex-wrap gap-1">
                                @foreach(auth()->user()->roles as $__role)
                                    <span style="background:rgba(255,255,255,.22);color:#fff;font-size:.65rem;font-weight:600;padding:.15rem .5rem;border-radius:20px;text-transform:uppercase;letter-spacing:.5px">
                                        {{ ucfirst($__role->name) }}
                                    </span>
                                @endforeach
                                @if(auth()->user()->roles->isEmpty())
                                    <span style="background:rgba(255,255,255,.15);color:#fff;font-size:.65rem;padding:.15rem .5rem;border-radius:20px">
                                        Sin rol asignado
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Unidad activa (si tiene) --}}
                @if($__unidadActiva ?? null)
                <div class="px-3 py-2" style="border-bottom:1px solid var(--card-border);background:var(--badge-light-bg)">
                    <div style="font-size:.72rem;color:var(--muted-color);text-transform:uppercase;letter-spacing:.4px;font-weight:600">Unidad activa</div>
                    <div style="font-size:.82rem;color:var(--body-color);font-weight:500;margin-top:.15rem">
                        <i class="fas fa-sitemap me-1" style="color:var(--accent);font-size:.7rem"></i>
                        {{ ($__unidadActiva ?? null)?->descripcion }}
                    </div>
                </div>
                @endif

                {{-- Acciones --}}
                <div class="p-2">
                    <button type="button"
                            class="dropdown-item d-flex align-items-center gap-2"
                            data-bs-toggle="modal"
                            data-bs-target="#modalCambiarPassword"
                            style="border-radius:8px;padding:.5rem .75rem;font-size:.84rem;color:var(--body-color)">
                        <i class="fas fa-key" style="color:var(--accent);width:16px"></i>
                        Cambiar contraseña
                    </button>

                    <hr class="my-1" style="border-color:var(--card-border)">

                    <form method="POST" action="{{ route('logout') }}" class="mb-0">
                        @csrf
                        <button type="submit"
                                class="dropdown-item d-flex align-items-center gap-2"
                                style="border-radius:8px;padding:.5rem .75rem;font-size:.84rem;color:#dc3545">
                            <i class="fas fa-sign-out-alt" style="width:16px"></i>
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endauth
    </div>
</header>
