/**
 * Helper frontend bersama: fetch ber-token, penanganan error, navbar, dan guard halaman.
 * Token Sanctum disimpan di localStorage.
 */
(function () {
    'use strict';

    const TOKEN_KEY = 'kos_token';
    const USER_KEY = 'kos_user';

    const Auth = {
        token: () => localStorage.getItem(TOKEN_KEY),
        user() {
            try { return JSON.parse(localStorage.getItem(USER_KEY)); } catch (e) { return null; }
        },
        save(token, user) {
            localStorage.setItem(TOKEN_KEY, token);
            localStorage.setItem(USER_KEY, JSON.stringify(user));
        },
        clear() {
            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem(USER_KEY);
        },
        isLoggedIn() { return !!this.token(); },
        isAdmin() { const u = this.user(); return !!u && u.role === 'admin'; },
    };

    class ApiError extends Error {
        constructor(status, message, errors) {
            super(message);
            this.status = status;
            this.errors = errors || null;
        }
    }

    function redirect(url) {
        if (!window.KOS_REDIRECTING) {
            window.KOS_REDIRECTING = true;
            window.location.replace(url);
        }
    }

    /**
     * api('/rooms', { method, body, params, auth })
     * auth:false dipakai untuk login/register (401 tidak memicu redirect).
     * Mengembalikan JSON body ({success, message, data, meta}); melempar ApiError jika gagal.
     */
    async function api(path, { method = 'GET', body = null, params = null, auth = true } = {}) {
        let url = '/api' + path;

        if (params) {
            const q = new URLSearchParams();
            Object.entries(params).forEach(([k, v]) => {
                if (v !== '' && v !== null && v !== undefined) q.append(k, v);
            });
            const qs = q.toString();
            if (qs) url += '?' + qs;
        }

        const headers = { Accept: 'application/json' };
        if (body !== null) headers['Content-Type'] = 'application/json';
        if (auth && Auth.token()) headers.Authorization = 'Bearer ' + Auth.token();

        let res;
        try {
            res = await fetch(url, { method, headers, body: body !== null ? JSON.stringify(body) : null });
        } catch (e) {
            throw new ApiError(0, 'Tidak dapat terhubung ke server.', null);
        }

        let json = null;
        try { json = await res.json(); } catch (e) { /* respons bukan JSON */ }

        if (res.status === 401 && auth) {
            Auth.clear();
            redirect('/login');
            throw new ApiError(401, (json && json.message) || 'Sesi berakhir, silakan login kembali.', null);
        }

        if (!res.ok) {
            throw new ApiError(res.status, (json && json.message) || 'Terjadi kesalahan.', (json && json.errors) || null);
        }

        return json;
    }

    // ---------- Alert & error validasi ----------
    function esc(value) {
        const div = document.createElement('div');
        div.textContent = value === null || value === undefined ? '' : String(value);
        return div.innerHTML;
    }

    function showAlert(type, message, container) {
        const target = container || document.getElementById('alert-area');
        if (!target) return;
        target.innerHTML =
            '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            esc(message) +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>';
    }

    function clearFieldErrors(form) {
        if (!form) return;
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback.js-error').forEach((el) => el.remove());
    }

    function showFieldErrors(form, errors) {
        Object.entries(errors || {}).forEach(([field, messages]) => {
            const input = form.querySelector('[name="' + field + '"]');
            if (!input) return;
            input.classList.add('is-invalid');
            const fb = document.createElement('div');
            fb.className = 'invalid-feedback js-error';
            fb.textContent = [].concat(messages).join(' ');
            input.insertAdjacentElement('afterend', fb);
        });
    }

    /** Tampilkan error API: pesan umum sebagai alert, error per-field di bawah input form. */
    function showError(err, form, alertContainer) {
        const message = err instanceof ApiError ? err.message : 'Terjadi kesalahan tak terduga.';
        if (form && err && err.errors) showFieldErrors(form, err.errors);
        showAlert('danger', message, alertContainer);
    }

    // ---------- Util ----------
    const rupiah = (n) => new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
    }).format(n);

    async function logout() {
        try { await api('/auth/logout', { method: 'POST' }); } catch (e) { /* tetap bersihkan sesi lokal */ }
        Auth.clear();
        redirect('/login');
    }

    // ---------- Guard halaman & navbar ----------
    function guard() {
        const mode = document.body.dataset.pageAuth;
        if (mode === 'required' && !Auth.isLoggedIn()) redirect('/login');
        if (mode === 'guest' && Auth.isLoggedIn()) redirect('/rooms');
    }

    function renderNavbar() {
        const user = Auth.user();
        const links = document.getElementById('nav-links');
        const box = document.getElementById('nav-user');
        if (!links || !box) return;

        if (!user) {
            links.innerHTML = '';
            box.innerHTML =
                '<a class="btn btn-sm btn-outline-light" href="/login">Login</a>' +
                '<a class="btn btn-sm btn-light" href="/register">Daftar</a>';
            return;
        }

        const path = window.location.pathname;
        links.innerHTML = (window.NAV_ITEMS || [])
            .filter((item) => item.roles.includes(user.role))
            .map((item) =>
                '<li class="nav-item"><a class="nav-link' + (path.startsWith(item.href) ? ' active' : '') +
                '" href="' + esc(item.href) + '">' + esc(item.label) + '</a></li>')
            .join('');

        box.innerHTML =
            '<span class="small">' + esc(user.name) +
            ' <span class="badge ' + (user.role === 'admin' ? 'bg-warning text-dark' : 'bg-info text-dark') + '">' +
            esc(user.role) + '</span></span>' +
            '<button type="button" class="btn btn-sm btn-outline-light" id="btn-logout">Logout</button>';
        document.getElementById('btn-logout').addEventListener('click', logout);
    }

    guard();
    renderNavbar();

    window.Auth = Auth;
    window.ApiError = ApiError;
    Object.assign(window, { api, showAlert, showError, clearFieldErrors, esc, rupiah, logout });
})();
