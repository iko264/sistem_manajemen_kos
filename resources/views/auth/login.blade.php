@extends('layouts.app')

@section('title', 'Login')
@section('page_auth', 'guest')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9 col-xl-8">
        <div class="card auth-card">
            <div class="row g-0">
                <div class="col-md-5 auth-aside p-4 p-lg-5 d-none d-md-flex flex-column justify-content-between">
                    <div>
                        <span class="auth-logo mb-4"><i class="bi bi-water"></i></span>
                        <h2 class="h4 fw-bold mb-2">Manajemen Kos</h2>
                        <p class="mb-4 opacity-75">Kelola kamar, penghuni, dan tagihan kos dalam satu tempat.</p>
                    </div>
                    <ul>
                        <li><i class="bi bi-check-circle-fill"></i> Data kamar selalu terbarui</li>
                        <li><i class="bi bi-check-circle-fill"></i> Tagihan dan pembayaran tercatat rapi</li>
                        <li><i class="bi bi-check-circle-fill"></i> Aman, data pribadi terlindungi</li>
                    </ul>
                </div>
                <div class="col-md-7">
                    <div class="card-body p-4 p-lg-5">
                        <h1 class="h4 mb-1">Selamat datang kembali</h1>
                        <p class="text-muted mb-4">Masuk untuk melanjutkan.</p>
                        <form id="loginForm" novalidate>
                            <div class="mb-3">
                                <label class="form-label" for="email"><i class="bi bi-envelope me-1"></i>Email</label>
                                <input type="email" class="form-control" id="email" name="email" autocomplete="email" required autofocus>
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="password"><i class="bi bi-lock me-1"></i>Password</label>
                                <div class="pw-wrap">
                                    <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
                                    <button type="button" class="pw-toggle" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2" id="btnSubmit">Masuk</button>
                        </form>
                        <p class="small text-muted mt-4 mb-0">Belum punya akun? <a href="/register" class="fw-semibold">Daftar</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.pw-toggle').forEach((b) => b.addEventListener('click', () => {
        const input = b.parentElement.querySelector('input');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        b.querySelector('i').className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
        b.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
    }));

    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btnSubmit');
        const label = btn.innerHTML;
        clearFieldErrors(form);
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memproses...';

        try {
            const res = await api('/auth/login', {
                method: 'POST',
                auth: false,
                body: { email: form.email.value, password: form.password.value },
            });
            Auth.save(res.data.token, res.data.user);
            window.location.href = '/rooms';
        } catch (err) {
            showError(err, form);
            btn.disabled = false;
            btn.innerHTML = label;
        }
    });
</script>
@endpush