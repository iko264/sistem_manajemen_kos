<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Beranda') | Manajemen Kos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
{{-- page_auth: 'required' (default, wajib login) atau 'guest' (halaman login/register) --}}
<body class="bg-light" data-page-auth="@yield('page_auth', 'required')">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">Manajemen Kos</a>
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
    @stack('scripts')
</body>
</html>
