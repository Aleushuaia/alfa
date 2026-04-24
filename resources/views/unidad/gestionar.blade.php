@extends('layouts.dashboard')

@section('title', 'Gestionar Unidad')
@section('page-title', 'Gestionar Unidad')
@section('breadcrumb', 'Seleccionar Unidad')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7 col-md-9">
        <div class="t-card">
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="fas fa-building-user" style="color:#fff;font-size:.9rem"></i>
                    </div>
                    <h5 class="mb-0" style="color:var(--heading-color);font-weight:700;font-size:1rem">
                        Seleccioná la unidad a gestionar
                    </h5>
                </div>
                <p class="small mb-0 mt-1" style="color:var(--muted-color)">
                    Sos administrador de las siguientes unidades. Hacé clic para administrar los usuarios.
                </p>
            </div>

            <div class="d-flex flex-column gap-2">
                @foreach($unidades as $unidad)
                <a href="{{ route('gestionar-unidad.show', $unidad) }}"
                   class="d-flex align-items-center gap-3 p-3 text-decoration-none"
                   style="border:1px solid var(--card-border);border-radius:12px;background:var(--badge-light-bg);transition:border-color .15s,background .15s"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.background='rgba(59,130,246,.05)'"
                   onmouseout="this.style.borderColor='var(--card-border)';this.style.background='var(--badge-light-bg)'">
                    <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="fas fa-sitemap" style="color:#fff"></i>
                    </div>
                    <div style="flex:1;overflow:hidden">
                        <div class="fw-semibold" style="color:var(--heading-color);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $unidad->descripcion }}
                        </div>
                        <div class="small" style="color:var(--muted-color)">
                            {{ $unidad->users_count ?? $unidad->users()->count() }} usuario(s) asignado(s)
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:var(--accent);opacity:.6"></i>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
