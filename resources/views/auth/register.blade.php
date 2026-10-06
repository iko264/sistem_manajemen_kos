@extends('layouts.app')

@section('title', 'Daftar')
@section('page_auth', 'guest')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Daftar sebagai Penghuni</h1>
                <form id="registerForm" novalidate>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="name">Nama lengkap</label>
                            <input type="text" class="form-control" id="name" name="name" required autofocus>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" autocomplete="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password">Password (min. 8 karakter)</label>
                            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">No. HP</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="identity_number">NIK</label>
                            <input type="text" class="form-control" id="identity_number" name="identity_number">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="address">Alamat asal</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-4" id="btnSubmit">Daftar</button>
                </form>
                <p class="small text-muted mt-3 mb-0">Sudah punya akun? <a href="/login">Masuk</a></p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btnSubmit');
        clearFieldErrors(form);
        btn.disabled = true;

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
        }
    });
</script>
@endpush
