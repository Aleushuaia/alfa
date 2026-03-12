{{-- resources/views/dashboard/partials/actividad_reciente.blade.php --}}
{{-- Widget 6: Timeline de actividad reciente --}}
<div class="card shadow-sm border-0 h-100">
    <div class="card-header bg-white border-0 pb-0">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="fas fa-history text-secondary me-2"></i>
            Actividad Reciente
        </h6>
    </div>
    <div class="card-body p-3" style="max-height: 420px; overflow-y: auto;">

        @forelse($items as $item)
        @php
            $config = match($item->tipo) {
                'expediente'   => ['bg' => 'primary', 'icon' => 'fa-folder-open'],
                'actuacion'    => ['bg' => 'success',  'icon' => 'fa-pen-nib'],
                'escrito'      => ['bg' => 'warning',  'icon' => 'fa-file-alt'],
                'notificacion' => ['bg' => 'info',     'icon' => 'fa-bell'],
                default        => ['bg' => 'secondary','icon' => 'fa-circle'],
            };
            $fecha = $item->fecha instanceof \Carbon\Carbon
                ? $item->fecha
                : \Carbon\Carbon::parse($item->fecha);
        @endphp

        <div class="d-flex gap-3 mb-3">
            {{-- Icono --}}
            <div class="flex-shrink-0">
                <span class="rounded-circle d-flex align-items-center justify-content-center
                             bg-{{ $config['bg'] }} bg-opacity-10 text-{{ $config['bg'] }}"
                      style="width:36px;height:36px;">
                    <i class="fas {{ $config['icon'] }} fa-sm"></i>
                </span>
            </div>

            {{-- Contenido --}}
            <div class="flex-grow-1 border-bottom pb-2">
                <div class="small fw-semibold text-body-secondary">
                    {{ $item->descripcion }}
                </div>
                <div class="text-muted" style="font-size:0.72rem;">
                    <i class="fas fa-clock me-1"></i>
                    {{ $fecha->diffForHumans() }}
                    &mdash;
                    {{ $fecha->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>

        @empty
        <div class="text-center text-muted py-4">
            <i class="fas fa-inbox fa-2x mb-2"></i>
            <div class="small">Sin actividad reciente</div>
        </div>
        @endforelse

    </div>
    @if(count($items) > 0)
    <div class="card-footer bg-white border-top-0 text-center">
        <a href="#" class="small text-primary">
            Ver todo el historial <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
    @endif
</div>
