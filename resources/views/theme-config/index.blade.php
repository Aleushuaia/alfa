@extends('layouts.dashboard')

@section('title', 'Colores del tema')
@section('page-title', 'Colores del tema')
@section('breadcrumb', 'Colores del tema')

@section('content')
<style>
    .preset-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: .75rem;
    }
    .preset-card {
        cursor: pointer;
        border: 2px solid var(--card-border);
        border-radius: 12px;
        padding: .75rem;
        text-align: center;
        background: var(--card-bg);
        transition: all .2s;
        user-select: none;
    }
    .preset-card:hover {
        border-color: var(--accent);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,.15);
    }
    .preset-card.active {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-glow, rgba(59,130,246,.25));
    }
    .preset-swatch {
        display: flex;
        gap: 4px;
        justify-content: center;
        margin-bottom: .5rem;
    }
    .preset-swatch span {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: block;
        border: 2px solid rgba(255,255,255,.15);
    }
    .preset-label {
        font-size: .78rem;
        font-weight: 600;
        color: var(--body-color);
    }

    /* Color picker rows */
    .color-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: .65rem 0;
        border-bottom: 1px solid var(--card-border);
    }
    .color-row:last-child { border-bottom: none; }
    .color-row-label {
        flex: 1;
        font-size: .85rem;
        font-weight: 500;
        color: var(--body-color);
    }
    .color-row-label small {
        display: block;
        font-size: .72rem;
        font-weight: 400;
        color: var(--muted-color);
        margin-top: 2px;
    }
    .color-picker-wrap {
        position: relative;
        width: 44px;
        height: 44px;
    }
    .color-picker-wrap input[type="color"] {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        border: 2px solid var(--card-border);
        border-radius: 10px;
        cursor: pointer;
        padding: 2px;
        background: var(--card-bg);
    }
    .color-hex {
        font-family: 'Fira Code', 'SF Mono', monospace;
        font-size: .78rem;
        color: var(--muted-color);
        width: 75px;
        text-align: center;
        background: var(--input-bg);
        border: 1px solid var(--input-border);
        border-radius: 6px;
        padding: .25rem .4rem;
        color: var(--input-color);
    }

    /* Mode tabs */
    .mode-tabs {
        display: flex;
        gap: .5rem;
        margin-bottom: 1.25rem;
    }
    .mode-tab {
        flex: 1;
        padding: .6rem;
        text-align: center;
        font-size: .85rem;
        font-weight: 600;
        border: 2px solid var(--card-border);
        border-radius: 10px;
        cursor: pointer;
        transition: all .2s;
        background: var(--card-bg);
        color: var(--body-color);
    }
    .mode-tab:hover { border-color: var(--accent); }
    .mode-tab.active {
        background: linear-gradient(135deg, var(--accent), var(--accent2));
        color: #fff;
        border-color: transparent;
    }
    .mode-panel { display: none; }
    .mode-panel.active { display: block; }

    /* Preview bar */
    .preview-bar {
        display: flex;
        align-items: stretch;
        border-radius: 12px;
        overflow: hidden;
        height: 60px;
        margin-top: 1rem;
        border: 1px solid var(--card-border);
    }
    .preview-segment {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .7rem;
        font-weight: 600;
        color: rgba(255,255,255,.9);
        text-shadow: 0 1px 3px rgba(0,0,0,.3);
    }

    /* Action buttons */
    .tc-actions {
        display: flex;
        gap: .75rem;
        margin-top: 1.5rem;
    }
    .tc-actions .btn {
        border-radius: 10px;
        font-weight: 600;
        font-size: .85rem;
        padding: .6rem 1.5rem;
    }
</style>

<div class="row g-4">
    {{-- Left: Presets --}}
    <div class="col-lg-4">
        <div class="k-card h-100">
            <div class="k-card-header">
                <h5 class="k-card-title"><i class="fas fa-swatchbook me-2"></i>Paletas predefinidas</h5>
            </div>
            <div class="k-card-body">
                <p style="font-size:.82rem;color:var(--muted-color);margin-bottom:1rem;">
                    Seleccioná una paleta para aplicar rápidamente los colores de acento a ambos temas.
                </p>
                <div class="preset-grid">
                    @foreach($presets as $key => $preset)
                    <div class="preset-card" data-preset="{{ $key }}" data-accent="{{ $preset['accent'] }}" data-accent2="{{ $preset['accent2'] }}">
                        <div class="preset-swatch">
                            <span style="background:{{ $preset['accent'] }}"></span>
                            <span style="background:{{ $preset['accent2'] }}"></span>
                        </div>
                        <div class="preset-label">{{ $preset['label'] }}</div>
                    </div>
                    @endforeach
                </div>

                <hr style="border-color:var(--card-border);margin:1.25rem 0">
                <p style="font-size:.78rem;color:var(--muted-color);margin-bottom:.75rem">
                    <i class="fas fa-info-circle me-1"></i>
                    La paleta cambia los colores de acento. Podés personalizar cada color individualmente en el panel derecho.
                </p>
            </div>
        </div>
    </div>

    {{-- Right: Fine-tune per theme --}}
    <div class="col-lg-8">
        <form action="{{ route('theme-config.save') }}" method="POST" id="themeForm">
            @csrf
            <div class="k-card">
                <div class="k-card-header d-flex align-items-center justify-content-between">
                    <h5 class="k-card-title mb-0"><i class="fas fa-palette me-2"></i>Personalización de colores</h5>
                </div>
                <div class="k-card-body">
                    {{-- Mode tabs --}}
                    <div class="mode-tabs">
                        <div class="mode-tab active" data-mode="light">
                            <i class="fas fa-sun me-1"></i> Tema Light
                        </div>
                        <div class="mode-tab" data-mode="dark">
                            <i class="fas fa-moon me-1"></i> Tema Dark
                        </div>
                    </div>

                    @foreach(['light' => 'Light', 'dark' => 'Dark'] as $mode => $label)
                    <div class="mode-panel {{ $mode === 'light' ? 'active' : '' }}" id="panel-{{ $mode }}">
                        @php
                            $fields = [
                                'accent'     => ['Acento principal', 'Color de botones, enlaces y elementos activos'],
                                'accent2'    => ['Acento secundario', 'Gradientes y acentos complementarios'],
                                'body_bg'    => ['Fondo de la página', 'Color de fondo general del contenido'],
                                'card_bg'    => ['Fondo de paneles', 'Color de fondo de tarjetas y paneles'],
                                'sidebar_bg' => ['Fondo del sidebar', 'Color de fondo del menú lateral'],
                                'topbar_bg'  => ['Fondo del header superior', 'Color de fondo de la barra superior'],
                            ];
                        @endphp

                        @foreach($fields as $key => [$title, $desc])
                        <div class="color-row">
                            <div class="color-row-label">
                                {{ $title }}
                                <small>{{ $desc }}</small>
                            </div>
                            <div class="color-picker-wrap">
                                <input type="color"
                                       name="colors[{{ $mode }}][{{ $key }}]"
                                       value="{{ $colors[$mode][$key] }}"
                                       id="color-{{ $mode }}-{{ $key }}"
                                       data-mode="{{ $mode }}"
                                       data-key="{{ $key }}">
                            </div>
                            <input type="text"
                                   class="color-hex"
                                   value="{{ $colors[$mode][$key] }}"
                                   data-for="color-{{ $mode }}-{{ $key }}"
                                   maxlength="7"
                                   pattern="^#[0-9a-fA-F]{6}$">
                        </div>
                        @endforeach

                        {{-- Preview --}}
                        <div class="preview-bar" id="preview-{{ $mode }}">
                            <div class="preview-segment" id="prev-{{ $mode }}-sidebar_bg">Sidebar</div>
                            <div class="preview-segment" id="prev-{{ $mode }}-topbar_bg">Header</div>
                            <div class="preview-segment" id="prev-{{ $mode }}-body_bg">Fondo</div>
                            <div class="preview-segment" id="prev-{{ $mode }}-card_bg">Panel</div>
                            <div class="preview-segment" id="prev-{{ $mode }}-accent">Acento</div>
                            <div class="preview-segment" id="prev-{{ $mode }}-accent2">Acento 2</div>
                        </div>
                    </div>
                    @endforeach

                    <div class="tc-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar colores
                        </button>
                        <a href="{{ route('theme-config.reset') }}"
                           class="btn btn-outline-secondary"
                           onclick="return confirm('¿Restaurar todos los colores a los valores predeterminados?')">
                            <i class="fas fa-undo me-2"></i>Restaurar predeterminados
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function() {
    const defaults = @json($defaults);

    // ── Mode tabs ────────────────────────────────────────────────
    document.querySelectorAll('.mode-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.mode-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.mode-panel').forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById('panel-' + tab.dataset.mode).classList.add('active');
        });
    });

    // ── Color picker <-> hex text sync ──────────────────────────
    document.querySelectorAll('input[type="color"]').forEach(picker => {
        picker.addEventListener('input', () => {
            const hex = document.querySelector(`.color-hex[data-for="${picker.id}"]`);
            if (hex) hex.value = picker.value;
            updatePreview(picker.dataset.mode);
            applyLivePreview();
        });
    });

    document.querySelectorAll('.color-hex').forEach(hex => {
        hex.addEventListener('input', () => {
            const val = hex.value.trim();
            if (/^#[0-9a-fA-F]{6}$/.test(val)) {
                const picker = document.getElementById(hex.dataset.for);
                if (picker) {
                    picker.value = val;
                    updatePreview(picker.dataset.mode);
                    applyLivePreview();
                }
            }
        });
    });

    // ── Preset click ────────────────────────────────────────────
    document.querySelectorAll('.preset-card').forEach(card => {
        card.addEventListener('click', () => {
            const accent  = card.dataset.accent;
            const accent2 = card.dataset.accent2;

            // Apply to both modes
            ['light', 'dark'].forEach(mode => {
                setColor(mode, 'accent', accent);
                setColor(mode, 'accent2', accent2);
            });

            // Visual feedback
            document.querySelectorAll('.preset-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');

            updatePreview('light');
            updatePreview('dark');
            applyLivePreview();
        });
    });

    function setColor(mode, key, value) {
        const picker = document.getElementById('color-' + mode + '-' + key);
        const hex    = document.querySelector(`.color-hex[data-for="color-${mode}-${key}"]`);
        if (picker) picker.value = value;
        if (hex)    hex.value = value;
    }

    function getColor(mode, key) {
        const picker = document.getElementById('color-' + mode + '-' + key);
        return picker ? picker.value : defaults[mode][key];
    }

    // ── Preview bars ────────────────────────────────────────────
    function updatePreview(mode) {
        ['sidebar_bg', 'topbar_bg', 'body_bg', 'card_bg', 'accent', 'accent2'].forEach(key => {
            const el = document.getElementById('prev-' + mode + '-' + key);
            if (el) el.style.background = getColor(mode, key);
        });
    }

    // Init previews
    updatePreview('light');
    updatePreview('dark');

    // ── Live preview: apply to current page in real-time ────────
    function applyLivePreview() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';

        const accent  = getColor(currentTheme, 'accent');
        const accent2 = getColor(currentTheme, 'accent2');
        const bodyBg  = getColor(currentTheme, 'body_bg');
        const cardBg  = getColor(currentTheme, 'card_bg');
        const sidebarBg = getColor(currentTheme, 'sidebar_bg');
        const topbarBg  = getColor(currentTheme, 'topbar_bg');

        document.documentElement.style.setProperty('--accent', accent);
        document.documentElement.style.setProperty('--accent2', accent2);
        document.documentElement.style.setProperty('--body-bg', bodyBg);
        document.documentElement.style.setProperty('--card-bg', cardBg);
        document.documentElement.style.setProperty('--sidebar-bg', sidebarBg);
        document.documentElement.style.setProperty('--topbar-bg', topbarBg);

        // Also update derived vars
        const r = parseInt(accent.slice(1,3),16);
        const g = parseInt(accent.slice(3,5),16);
        const b = parseInt(accent.slice(5,7),16);
        document.documentElement.style.setProperty('--nav-active-bg', `rgba(${r},${g},${b},.18)`);
        document.documentElement.style.setProperty('--nav-active-border', accent);
        document.documentElement.style.setProperty('--link-color', currentTheme === 'dark' ? accent2 : accent);
    }

    // ── Highlight current preset ────────────────────────────────
    const currentAccent = getColor('light', 'accent');
    document.querySelectorAll('.preset-card').forEach(card => {
        if (card.dataset.accent === currentAccent) card.classList.add('active');
    });
})();
</script>
@endpush
