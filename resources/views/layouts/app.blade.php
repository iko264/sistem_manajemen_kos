<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Beranda') | Manajemen Kos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}?v={{ filemtime(public_path('css/theme.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
{{-- page_auth: 'required' (default, wajib login) atau 'guest' (halaman login/register) --}}
<body data-page-auth="@yield('page_auth', 'required')">
    <nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="bi bi-water me-2"></i>Manajemen Kos</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"
                    aria-controls="navMenu" aria-expanded="false" aria-label="Buka menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav me-auto" id="nav-links"></ul>
                <div class="d-flex align-items-center gap-2 text-white" id="nav-user"></div>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div id="alert-area"></div>
        @yield('content')
    </main>

    @include('partials.nav-items')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/api.js') }}"></script>
    <script src="{{ asset('js/api-compat.js') }}"></script>
    @stack('scripts')
</body>
</html>