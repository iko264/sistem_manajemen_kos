@extends('layouts.app')

@section('title', 'Daftar')
@section('page_auth', 'guest')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <div class="card auth-card">
            <div class="row g-0">
                <div class="col-md-4 auth-aside p-4 p-lg-5 d-none d-md-flex flex-column justify-content-between">
                    <div>
                        <span class="auth-logo mb-4"><i class="bi bi-person-plus"></i></span>
                        <h2 class="h4 fw-bold mb-2">Bergabung</h2>
                        <p class="mb-4 opacity-75">Daftar sebagai penghuni untuk melihat kamar dan tagihanmu.</p>
                    </div>
                    <ul>
                        <li><i class="bi bi-check-circle-fill"></i> Lihat daftar kamar</li>
                        <li><i class="bi bi-check-circle-fill"></i> Pantau tagihan sendiri</li>
                        <li><i class="bi bi-check-circle-fill"></i> Data pribadimu hanya dilihat pengelola</li>
                    </ul>
                </div>
                <div class="col-md-8">
                    <div class="card-body p-4 p-lg-5">
                        <h1 class="h4 mb-1">Daftar sebagai Penghuni</h1>
                        <p class="text-muted mb-4">Isi data di bawah untuk membuat akun.</p>
                        <form id="registerForm" novalidate>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="name"><i class="bi bi-person me-1"></i>Nama lengkap</label>
                                    <input type="text" class="form-control" id="name" name="name" required autofocus>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="email"><i class="bi bi-envelope me-1"></i>Email</label>
                                    <input type="email" class="form-control" id="email" name="email" autocomplete="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="password"><i class="bi bi-lock me-1"></i>Password (min. 8 karakter)</label>
                                    <div class="pw-wrap">
                                        <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                                        <button type="button" class="pw-toggle" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="phone"><i class="bi bi-telephone me-1"></i>No. HP</label>
                                    <input type="text" class="form-control" id="phone" name="phone">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="identity_number"><i class="bi bi-person-vcard me-1"></i>NIK</label>
                                    <input type="text" class="form-control" id="identity_number" name="identity_number">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="address"><i class="bi bi-geo-alt me-1"></i>Alamat asal</label>
                                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 mt-4" id="btnSubmit">Daftar</button>
                        </form>
                        <p class="small text-muted mt-4 mb-0">Sudah punya akun? <a href="/login" class="fw-semibold">Masuk</a></p>
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

    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btnSubmit');
        const label = btn.innerHTML;
        clearFieldErrors(form);
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memproses...';

        const body = { password: form.password.value };
        ['name', 'email', 'phone', 'identity_number', 'address'].forEach((f) => {
            const v = form[f].value.trim();
            if (v !== '') body[f] = v;
        });

        try {
            const res = await api('/auth/register', { method: 'POST', auth: false, body });
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