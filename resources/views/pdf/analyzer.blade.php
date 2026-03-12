@extends('layouts.dashboard')

@section('title', 'Analizador de PDF — SAE Kayen')
@section('page-title', 'Análisis y anonimización de documentos PDF')
@section('breadcrumb', 'Análisis de PDF')

@push('styles')
<style>
    /* ── Entidades ───────────────────────────────────────── */
    .entity {
        padding: 2px 5px;
        border-radius: 4px;
        font-weight: 500;
        cursor: pointer;
        position: relative;
        display: inline;
    }
    .entity.person   { background: #ffcccc; }
    .entity.org      { background: #cce5ff; }
    .entity.location { background: #ccffcc; }
    .entity.date     { background: #ffe0b3; }
    .entity.dni      { background: #e0e0e0; }
    .entity.email    { background: #ccf2ff; }
    .entity.phone    { background: #ffffcc; }
    .entity.misc     { background: #e0ccff; }

    /* ── Menú flotante al hacer clic ────────────────────── */
    .entity-menu {
        display: none;
        position: absolute;
        z-index: 9999;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,.15);
        padding: 4px 0;
        min-width: 175px;
        top: 100%;
        left: 0;
    }
    .entity-menu button {
        display: block;
        width: 100%;
        padding: 6px 14px;
        background: none;
        border: none;
        text-align: left;
        font-size: .85rem;
        cursor: pointer;
    }
    .entity-menu button:hover { background: #f5f5f5; }
    .entity.ignored { opacity: .35; text-decoration: line-through; }

    /* ── Editor ─────────────────────────────────────────── */
    #editor-container {
        border: 1px solid #dee2e6;
        border-radius: .375rem;
        min-height: 420px;
        max-height: 620px;
        overflow-y: auto;
        padding: 1rem 1.25rem;
        background: #fefefe;
        line-height: 1.8;
        font-size: .95rem;
    }

    /* ── Leyenda ─────────────────────────────────────────── */
    .legend-dot {
        display: inline-block;
        width: 14px;
        height: 14px;
        border-radius: 3px;
        margin-right: 5px;
        vertical-align: middle;
    }
</style>
@endpush

@section('content')

{{-- ── Errores ──────────────────────────────────────────────────────────── --}}
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-3">

    {{-- ── Col izquierda: Formulario + Leyenda ─────────────────────────── --}}
    <div class="col-lg-3">

        {{-- Subida de archivo --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">
                <i class="fas fa-file-pdf me-2 text-danger"></i>Subir documento
            </div>
            <div class="card-body">
                <form method="POST"
                      action="{{ route('pdf-analyzer.process') }}"
                      enctype="multipart/form-data"
                      id="uploadForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Archivo PDF (máx. 20 MB)</label>
                        <input type="file"
                               name="pdf"
                               accept=".pdf"
                               class="form-control form-control-sm"
                               required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100" id="btnAnalizar">
                        <i class="fas fa-search me-1"></i>Analizar documento
                    </button>
                </form>
            </div>
        </div>

        {{-- Leyenda de colores --}}
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">
                <i class="fas fa-palette me-2"></i>Leyenda
            </div>
            <div class="card-body p-2">
                <table class="table table-sm table-borderless mb-0" style="font-size:.82rem;">
                    <tr><td><span class="legend-dot" style="background:#ffcccc"></span>Persona</td></tr>
                    <tr><td><span class="legend-dot" style="background:#cce5ff"></span>Organización</td></tr>
                    <tr><td><span class="legend-dot" style="background:#ccffcc"></span>Lugar</td></tr>
                    <tr><td><span class="legend-dot" style="background:#ffe0b3"></span>Fecha</td></tr>
                    <tr><td><span class="legend-dot" style="background:#e0e0e0"></span>DNI</td></tr>
                    <tr><td><span class="legend-dot" style="background:#ccf2ff"></span>Email</td></tr>
                    <tr><td><span class="legend-dot" style="background:#ffffcc"></span>Teléfono</td></tr>
                    <tr><td><span class="legend-dot" style="background:#e0ccff"></span>Otros</td></tr>
                </table>
            </div>
        </div>

    </div>

    {{-- ── Col derecha: Editor + Botones ───────────────────────────────── --}}
    <div class="col-lg-9">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold">
                    <i class="fas fa-file-alt me-2"></i>Texto analizado
                </span>
                @if(isset($analyzedHtml))
                <div class="d-flex gap-2">
                    <button class="btn btn-warning btn-sm" id="btnAnonimizar">
                        <i class="fas fa-user-secret me-1"></i>Anonimizar sensibles
                    </button>
                    <button class="btn btn-success btn-sm" id="btnExportar">
                        <i class="fas fa-file-export me-1"></i>Exportar PDF anonimizado
                    </button>
                </div>
                @endif
            </div>

            <div class="card-body">
                @if(isset($analyzedHtml))
                    {{-- Editor de texto con entidades --}}
                    <div id="editor-container" contenteditable="true">
                        {!! $analyzedHtml !!}
                    </div>
                    {{-- Formulario oculto para exportar --}}
                    <form method="GET"
                          action="{{ route('pdf-analyzer.export') }}"
                          id="exportForm">
                    </form>
                @else
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-file-upload fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">Sube un PDF para comenzar el análisis.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Estadísticas agrupadas de entidades --}}
        @if(isset($groupedEntities) && count($groupedEntities))
        <div class="card shadow-sm mt-3">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <div><i class="fas fa-list-ul me-2"></i>Entidades detectadas (agrupadas)</div>
                <span class="badge bg-secondary ms-1">{{ count($groupedEntities) }} tipos</span>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size:.83rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Texto</th>
                                <th>Tipo</th>
                                <th>Veces</th>
                                <th>Posiciones (inicio-fin)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groupedEntities as $g)
                            <tr>
                                <td>{{ $g['text'] }}</td>
                                <td><span class="badge" style="background:var(--entity-{{ strtolower($g['label'] ?? '') }}, #ddd);color:#333">{{ $g['label'] }}</span></td>
                                <td>{{ $g['count'] }}</td>
                                <td>
                                    @foreach($g['positions'] as $p)
                                        @if($loop->first) @endif
                                        <small class="text-muted">{{ $p['start'] ?? '-' }}-{{ $p['end'] ?? '-' }}</small>@if(!$loop->last), @endif
                                    @endforeach
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

    </div>{{-- /col-lg-9 --}}

</div>{{-- /row --}}

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    // ── Loader en el botón Analizar ───────────────────────────────────────
    const uploadForm = document.getElementById('uploadForm');
    const btnAnalizar = document.getElementById('btnAnalizar');
    if (uploadForm) {
        uploadForm.addEventListener('submit', () => {
            if (btnAnalizar) {
                btnAnalizar.disabled = true;
                btnAnalizar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando…';
            }
        });
    }

    // ── Menú flotante en entidades ────────────────────────────────────────
    let activeMenu = null;

    function closeActiveMenu() {
        if (activeMenu) {
            activeMenu.style.display = 'none';
            activeMenu = null;
        }
    }

    document.addEventListener('click', (e) => {
        const span = e.target.closest('.entity');
        closeActiveMenu();

        if (!span) return;

        // Crear o reutilizar menú
        let menu = span.querySelector('.entity-menu');
        if (!menu) {
            menu = document.createElement('div');
            menu.className = 'entity-menu';
            menu.innerHTML = `
                <button data-action="approve">✅ Aprobar entidad</button>
                <button data-action="ignore">🚫 Ignorar entidad</button>
                <button data-action="anonimize">🔒 Anonimizar entidad</button>
            `;
            span.style.position = 'relative';
            span.appendChild(menu);
        }

        menu.style.display = 'block';
        activeMenu = menu;
        e.stopPropagation();

        // Acciones del menú
        menu.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', (ev) => {
                ev.stopPropagation();
                const action = btn.dataset.action;
                if (action === 'approve') {
                    span.style.outline = '2px solid #28a745';
                } else if (action === 'ignore') {
                    span.classList.toggle('ignored');
                } else if (action === 'anonimize') {
                    const label = span.dataset.label || 'DATO';
                    span.outerHTML = `[${label.toUpperCase()}]`;
                }
                closeActiveMenu();
            }, { once: true });
        });
    });

    // ── Anonimización automática ──────────────────────────────────────────
    const btnAnonimizar = document.getElementById('btnAnonimizar');
    if (btnAnonimizar) {
        btnAnonimizar.addEventListener('click', async () => {
            const editor = document.getElementById('editor-container');
            const currentHtml = editor.innerHTML;

            btnAnonimizar.disabled = true;
            btnAnonimizar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Anonimizando…';

            try {
                const res = await fetch("{{ route('pdf-analyzer.anonimize') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ html: currentHtml }),
                });

                if (!res.ok) throw new Error('Error HTTP ' + res.status);
                const data = await res.json();
                editor.innerHTML = data.html;
            } catch (err) {
                alert('No se pudo anonimizar: ' + err.message);
            } finally {
                btnAnonimizar.disabled = false;
                btnAnonimizar.innerHTML = '<i class="fas fa-user-secret me-1"></i>Anonimizar sensibles';
            }
        });
    }

    // ── Exportar PDF ──────────────────────────────────────────────────────
    const btnExportar = document.getElementById('btnExportar');
    if (btnExportar) {
        btnExportar.addEventListener('click', () => {
            // Antes de exportar, sincroniza el HTML del editor con la sesión
            const editor = document.getElementById('editor-container');
            const currentHtml = editor.innerHTML;

            fetch("{{ route('pdf-analyzer.anonimize') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ html: currentHtml }),
            }).then(() => {
                document.getElementById('exportForm').submit();
            }).catch(() => {
                document.getElementById('exportForm').submit();
            });
        });
    }

});
</script>
@endpush
