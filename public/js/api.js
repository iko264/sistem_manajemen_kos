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

    // ---------- Indikator loading ----------
    let pending = 0;

    function progress(delta) {
        pending = Math.max(0, pending + delta);
        let bar = document.getElementById('api-progress');
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'api-progress';
            bar.className = 'api-progress';
            document.body.appendChild(bar);
        }
        bar.classList.toggle('active', pending > 0);
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
        let json = null;

        progress(1);
        try {
            try {
                res = await fetch(url, { method, headers, body: body !== null ? JSON.stringify(body) : null });
            } catch (e) {
                throw new ApiError(0, 'Tidak dapat terhubung ke server.', null);
            }

            try { json = await res.json(); } catch (e) { /* respons bukan JSON */ }
        } finally {
            progress(-1);
        }

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

    // ---------- Alert, toast & error validasi ----------
    function esc(value) {
        const div = document.createElement('div');
        div.textContent = value === null || value === undefined ? '' : String(value);
        return div.innerHTML;
    }

    const TOAST_ICONS = {
        success: 'bi-check-circle-fill',
        danger: 'bi-exclamation-triangle-fill',
        warning: 'bi-exclamation-circle-fill',
        info: 'bi-info-circle-fill',
    };

    function toast(type, message) {
        let stack = document.getElementById('toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.id = 'toast-stack';
            stack.className = 'toast-stack';
            document.body.appendChild(stack);
        }
        while (stack.children.length >= 4) stack.firstElementChild.remove();

        const el = document.createElement('div');
        el.className = 'app-toast app-toast-' + type;
        el.setAttribute('role', type === 'danger' ? 'alert' : 'status');
        el.innerHTML =
            '<i class="bi ' + (TOAST_ICONS[type] || TOAST_ICONS.info) + '"></i>' +
            '<div class="app-toast-text">' + esc(message) + '</div>' +
            '<button type="button" class="btn-close" aria-label="Tutup"></button>';

        const close = () => {
            el.classList.add('hide');
            setTimeout(() => el.remove(), 250);
        };
        el.querySelector('.btn-close').addEventListener('click', close);
        stack.appendChild(el);
        setTimeout(close, type === 'danger' ? 7000 : 4000);
    }

    /** Tanpa container: tampil sebagai toast. Dengan container (mis. di dalam modal): alert inline. */
    function showAlert(type, message, container) {
        if (!container) {
            toast(type, message);
            return;
        }
        container.innerHTML =
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

    /** Tampilkan error API: pesan umum sebagai toast/alert, error per-field di bawah input form. */
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

    const initials = (name) => String(name || '?').trim().split(/\s+/).slice(0, 2)
        .map((w) => w.charAt(0).toUpperCase()).join('');

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
                '" href="' + esc(item.href) + '">' +
                (item.icon ? '<i class="bi ' + esc(item.icon) + ' me-1"></i>' : '') +
                esc(item.label) + '</a></li>')
            .join('');

        box.innerHTML =
            '<span class="nav-avatar" aria-hidden="true">' + esc(initials(user.name)) + '</span>' +
            '<span class="small d-none d-md-inline">' + esc(user.name) + '</span>' +
            '<span class="badge nav-role">' + esc(user.role) + '</span>' +
            '<button type="button" class="btn btn-sm btn-outline-light" id="btn-logout">' +
            '<i class="bi bi-box-arrow-right me-1"></i>Logout</button>';
        document.getElementById('btn-logout').addEventListener('click', logout);
    }

    guard();
    renderNavbar();

    window.Auth = Auth;
    window.ApiError = ApiError;
    Object.assign(window, { api, showAlert, showError, clearFieldErrors, esc, rupiah, logout });
})();