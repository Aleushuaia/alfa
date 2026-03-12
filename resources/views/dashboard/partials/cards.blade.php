{{-- resources/views/dashboard/partials/cards.blade.php --}}
<div class="row g-3 mb-3">
    @foreach($cards as $card)
    @php
        $diff     = $card['valor'] - $card['anterior'];
        $pct      = $card['anterior'] > 0
                        ? round(($diff / $card['anterior']) * 100, 1)
                        : 0;
        $trending = $diff >= 0;
    @endphp
    <div class="col-xl-3 col-md-6">
        <div class="card card-widget shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-3 p-3 me-3 bg-{{ $card['color'] }} bg-opacity-10">
                    <i class="{{ $card['icono'] }} fa-2x text-{{ $card['color'] }}"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="text-muted small text-uppercase fw-semibold">{{ $card['titulo'] }}</div>
                    <div class="h3 mb-1 fw-bold">{{ number_format($card['valor']) }}</div>
                    <div class="small {{ $trending ? 'badge-trend-up' : 'badge-trend-down' }}">
                        <i class="fas {{ $trending ? 'fa-arrow-up' : 'fa-arrow-down' }} me-1"></i>
                        {{ abs($pct) }}%
                        <span class="text-muted ms-1">vs mes anterior</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
