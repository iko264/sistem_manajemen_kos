/**
 * Adaptor: menyediakan objek global `Api` dan event `api:ready` untuk halaman billing dan dashboard,
 * dengan memakai helper dari api.js (api, Auth, showAlert, showError, clearFieldErrors, esc, rupiah).
 */
(function () {
    'use strict';

    if (typeof window.api !== 'function' || !window.Auth) return;

    // Halaman memanggil path lengkap ('/api/invoices'); api() sudah menambahkan prefix '/api'.
    const strip = (p) => String(p).replace(/^\/api(?=\/)/, '');

    async function upload(path, formData, method) {
        const headers = { Accept: 'application/json' };
        if (Auth.token()) headers.Authorization = 'Bearer ' + Auth.token();

        let res;
        let json = null;
        try {
            res = await fetch('/api' + strip(path), { method: method || 'POST', headers, body: formData });
        } catch (e) {
            throw new ApiError(0, 'Tidak dapat terhubung ke server.', null);
        }
        try { json = await res.json(); } catch (e) { /* respons bukan JSON */ }

        if (res.status === 401) {
            Auth.clear();
            window.location.replace('/login');
            throw new ApiError(401, (json && json.message) || 'Sesi berakhir, silakan login kembali.', null);
        }
        if (!res.ok) {
            throw new ApiError(res.status, (json && json.message) || 'Terjadi kesalahan.', (json && json.errors) || null);
        }
        return json;
    }

    function showErrors(form, err) {
        const errors = err && err.errors ? err.errors : {};
        Object.entries(errors).forEach(([field, messages]) => {
            const input = form.querySelector('[name="' + field + '"]');
            if (!input) return;
            input.classList.add('is-invalid');
            const fb = document.createElement('div');
            fb.className = 'invalid-feedback js-error';
            fb.textContent = [].concat(messages).join(' ');
            input.insertAdjacentElement('afterend', fb);
        });
    }

    function renderPagination(container, meta, onPage) {
        if (!container) return;
        if (!meta) { container.innerHTML = ''; return; }

        const item = (label, page, disabled, active) =>
            '<li class="page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '">' +
            '<a class="page-link" href="#" data-page="' + page + '">' + label + '</a></li>';

        let html = item('&laquo;', meta.current_page - 1, meta.current_page <= 1, false);
        const from = Math.max(1, meta.current_page - 2);
        const to = Math.min(meta.last_page, meta.current_page + 2);
        for (let p = from; p <= to; p++) html += item(p, p, false, p === meta.current_page);
        html += item('&raquo;', meta.current_page + 1, meta.current_page >= meta.last_page, false);

        container.innerHTML =
            '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
            '<small class="text-muted">Halaman ' + meta.current_page + ' dari ' + meta.last_page +
            ' (total ' + meta.total + ' data)</small>' +
            (meta.last_page > 1 ? '<nav aria-label="Navigasi halaman"><ul class="pagination pagination-sm mb-0">' + html + '</ul></nav>' : '') +
            '</div>';

        container.querySelectorAll('a[data-page]').forEach((a) => a.addEventListener('click', (e) => {
            e.preventDefault();
            if (a.parentElement.classList.contains('disabled')) return;
            onPage(Number(a.dataset.page));
        }));
    }

    window.Api = {
        get: (path, params) => api(strip(path), { params: params || null }),
        post: (path, body) => api(strip(path), { method: 'POST', body: body === undefined ? null : body }),
        put: (path, body) => api(strip(path), { method: 'PUT', body: body === undefined ? null : body }),
        patch: (path, body) => api(strip(path), { method: 'PATCH', body: body === undefined ? null : body }),
        delete: (path) => api(strip(path), { method: 'DELETE' }),
        upload,
        isAdmin: () => Auth.isAdmin(),
        esc,
        rupiah,
        alert: (type, message, container) => showAlert(type, message, container),
        clearErrors: (form) => clearFieldErrors(form),
        showErrors,
        handleError: (err, form, container) => showError(err, form, container),
        renderPagination,
    };

    // Beri tahu halaman bahwa Api siap (halaman mendengarkan event ini untuk memuat data pertama kali).
    document.addEventListener('DOMContentLoaded', () => {
        if (window.KOS_REDIRECTING) return;
        document.dispatchEvent(new CustomEvent('api:ready'));
    });
})();