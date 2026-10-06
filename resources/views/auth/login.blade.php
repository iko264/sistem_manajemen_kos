@extends('layouts.app')

@section('title', 'Login')
@section('page_auth', 'guest')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Masuk</h1>
                <form id="loginForm" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" autocomplete="email" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="btnSubmit">Masuk</button>
                </form>
                <p class="small text-muted mt-3 mb-0">Belum punya akun? <a href="/register">Daftar</a></p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btnSubmit');
        clearFieldErrors(form);
        btn.disabled = true;

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
        }
    });
</script>
@endpush
