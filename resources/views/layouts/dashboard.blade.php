<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'SAE Kayen'))</title>

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('jur2d2.png') }}" sizes="100x100" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('jur2d2.png') }}">

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- Bootstrap 5.3 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    {{-- Font Awesome 6 --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    {{-- Layout CSS --}}
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">

    {{-- CSS adicional de cada vista --}}
    @stack('styles')
</head>
<body>

{{--  Sidebar  --}}
@include('layouts.dashboard._sidebar')

{{--  Topbar  --}}
@include('layouts.dashboard._topbar')

{{--  Page wrapper  --}}
<div id="page-wrapper">
    <main id="page-content">

        {{-- Flash alerts --}}
        @if(session('success'))
            <div class="alert flash-alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert flash-alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')

    </main>

    <footer id="page-footer">
        <span>&copy; {{ date('Y') }} {{ config('app.name', 'Pento') }}. Todos los derechos reservados.</span>
    </footer>
</div>

{{--  Scripts base  --}}
@include('layouts.dashboard._scripts')

{{-- Scripts adicionales de cada vista --}}
@stack('scripts')

</body>
</html>