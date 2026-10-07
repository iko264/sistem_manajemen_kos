@extends('layouts.app')

@section('title', 'Penghuni')

@section('content')
<div class="page-head">
    <div>
        <h1 class="h4"><span class="page-icon"><i class="bi bi-people"></i></span>Penghuni</h1>
        <p class="page-sub">Riwayat penghunian, check-in, dan check-out.</p>
    </div>
    <button type="button" class="btn btn-primary admin-only d-none" id="btnCheckin"><i class="bi bi-box-arrow-in-right me-1"></i>Check-in</button>
</div>

<form id="filterForm" class="card card-body shadow-sm mb-3 admin-only d-none" novalidate>
    <div class="row g-2">
        <div class="col-12 col-md-4 admin-only d-none">
            <input type="text" class="form-control" name="search" placeholder="Cari nama penghuni">
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="status">
                <option value="">Semua status</option>
                <option value="active">Aktif</option>
                <option value="ended">Selesai</option>
            </select>
        </div>
        <div class="col-6 col-md-3 admin-only d-none">
            <select class="form-select" name="room_id" id="filterRoom">
                <option value="">Semua kamar</option>
            </select>
        </div>
        <div class="col-6 col-md-1 admin-only d-none">
            <select class="form-select" name="per_page" aria-label="Jumlah per halaman">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>
    <div class="d-flex gap-2 mt-2">
        <button type="submit" class="btn btn-dark btn-sm">Terapkan</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnReset">Reset</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-stack">
            <thead class="table-light">
                <tr>
                    <th>Penghuni</th>
                    <th>Kamar</th>
                    <th>Tanggal masuk</th>
                    <th>Tanggal keluar</th>
                    <th>Status</th>
                    <th class="admin-only d-none text-end">Aksi</th>
                </tr>
            </thead>
            <tbody id="tenanciesBody"></tbody>
        </table>
    </div>
    <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
        <small class="text-muted" id="pagerInfo"></small>
        <nav aria-label="Navigasi halaman"><ul class="pagination pagination-sm mb-0" id="pager"></ul></nav>
    </div>
</div>

{{-- Modal check-in --}}
<div class="modal fade" id="checkinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="checkinForm" novalidate>
            <div class="modal-header">
                <h2 class="modal-title h5">Check-in Penghuni</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="modalAlert"></div>
                <div class="mb-3">
                    <label class="form-label" for="c_user">Penghuni (belum punya kamar)</label>
                    <select class="form-select" id="c_user" name="user_id"></select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="c_room">Kamar (tersedia)</label>
                    <select class="form-select" id="c_room" name="room_id"></select>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="c_date">Tanggal masuk</label>
                    <input type="date" class="form-control" id="c_date" name="start_date">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSaveCheckin">Check-in</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal konfirmasi check-out --}}
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5">Check-out</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body" id="checkoutText"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btnConfirmCheckout">Check-out</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal identitas penghuni per kamar --}}
<div class="modal fade" id="identityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="identityTitle">Identitas Penghuni</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body" id="identityBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    if (window.KOS_REDIRECTING) return;

    const STATUS = {
                active: { label: 'Aktif',   cls: 'badge-soft-success', icon: 'bi-check-circle' },
        ended:  { label: 'Selesai', cls: 'badge-soft-secondary', icon: 'bi-clock-history' },
    };

    const isAdmin = Auth.isAdmin();
    const state = { page: 1, rows: new Map(), checkoutId: null };
    const colspan = isAdmin ? 6 : 5;

    const filterForm = document.getElementById('filterForm');
    const checkinForm = document.getElementById('checkinForm');
    const body = document.getElementById('tenanciesBody');
    const checkinModal = new bootstrap.Modal('#checkinModal');
    const checkoutModal = new bootstrap.Modal('#checkoutModal');
    const identityModal = new bootstrap.Modal('#identityModal');

    document.querySelectorAll('.admin-only').forEach((el) => el.classList.toggle('d-none', !isAdmin));

    const fmtDate = (d) => {
        if (!d) return '-';
        const p = String(d).split('-');
        return p.length === 3 ? p[2] + '-' + p[1] + '-' + p[0] : d;
    };

    const today = () => {
        const d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    };

    // ---------- Filter kamar (admin) ----------
    async function loadRoomFilter() {
        if (!isAdmin) return;
        try {
            const res = await api('/rooms', { params: { per_page: 50 } });
            const sel = document.getElementById('filterRoom');
            sel.insertAdjacentHTML('beforeend', res.data.map((r) =>
                '<option value="' + r.id + '">Kamar ' + esc(r.number) + '</option>').join(''));
        } catch (err) { /* filter kamar opsional */ }
    }

    // ---------- Daftar ----------
    async function loadTenancies(page) {
        state.page = page || 1;
        clearFieldErrors(filterForm);

        const params = { page: state.page };
        new FormData(filterForm).forEach((v, k) => { if (v !== '') params[k] = v; });

        body.innerHTML = '<tr><td colspan="' + colspan + '" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat...</td></tr>';

        try {
            const res = await api('/tenancies', { params });
            renderRows(res.data);
            renderPager(res.meta);
        } catch (err) {
            body.innerHTML = '<tr><td colspan="' + colspan + '" class="text-center text-danger py-4">Gagal memuat data.</td></tr>';
            document.getElementById('pager').innerHTML = '';
            document.getElementById('pagerInfo').textContent = '';
            showError(err, filterForm);
        }
    }

    function renderRows(rows) {
        state.rows.clear();

        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="' + colspan + '" class="text-center empty-state"><i class="bi bi-people"></i>Belum ada data penghuni.</td></tr>';
            return;
        }

        body.innerHTML = rows.map((t) => {
            state.rows.set(t.id, t);
            const st = STATUS[t.status] || { label: t.status, cls: 'bg-secondary' };
            const name = t.user ? t.user.name : '-';
            const email = t.user ? t.user.email : '';
            const room = t.room ? t.room.number : '-';
            const type = t.room ? t.room.type : '';

            let actions = '';
            if (isAdmin) {
                actions = '<td class="text-end text-nowrap cell-actions">' + (t.status === 'active'
                    ? '<button type="button" class="btn btn-sm btn-outline-primary me-1" data-action="identity" data-id="' + t.id + '">Identitas</button>' +
                      '<button type="button" class="btn btn-sm btn-outline-danger" data-action="checkout" data-id="' + t.id + '">Check-out</button>'
                    : '') + '</td>';
            }

            const ini = String(name).trim().split(/\s+/).slice(0, 2).map((w) => w.charAt(0).toUpperCase()).join('');
            return '<tr>' +
                '<td data-label="Penghuni"><div class="d-flex align-items-center gap-2 text-start"><span class="avatar-sm">' + esc(ini) + '</span><div><div class="fw-semibold">' + esc(name) + '</div><div class="small text-muted">' + esc(email) + '</div></div></div></td>' +
                '<td data-label="Kamar"><span class="fw-semibold">' + esc(room) + '</span> <span class="small text-muted text-capitalize">' + esc(type) + '</span></td>' +
                '<td data-label="Tanggal masuk">' + esc(fmtDate(t.start_date)) + '</td>' +
                '<td data-label="Tanggal keluar">' + esc(fmtDate(t.end_date)) + '</td>' +
                '<td data-label="Status"><span class="badge badge-soft ' + st.cls + '"><i class="bi ' + (st.icon || 'bi-circle') + '"></i>' + esc(st.label) + '</span></td>' +
                actions + '</tr>';
        }).join('');
    }

    function renderPager(meta) {
        document.getElementById('pagerInfo').textContent =
            'Halaman ' + meta.current_page + ' dari ' + meta.last_page + ' (total ' + meta.total + ' data)';

        const item = (label, page, disabled, active) =>
            '<li class="page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '">' +
            '<a class="page-link" href="#" data-page="' + page + '">' + label + '</a></li>';

        let html = item('&laquo;', meta.current_page - 1, meta.current_page <= 1, false);
        const from = Math.max(1, meta.current_page - 2);
        const to = Math.min(meta.last_page, meta.current_page + 2);
        for (let p = from; p <= to; p++) html += item(p, p, false, p === meta.current_page);
        html += item('&raquo;', meta.current_page + 1, meta.current_page >= meta.last_page, false);

        document.getElementById('pager').innerHTML = meta.last_page > 1 ? html : '';
    }

    document.getElementById('pager').addEventListener('click', (e) => {
        const link = e.target.closest('a[data-page]');
        if (!link) return;
        e.preventDefault();
        if (link.parentElement.classList.contains('disabled')) return;
        loadTenancies(Number(link.dataset.page));
    });

    filterForm.addEventListener('submit', (e) => { e.preventDefault(); loadTenancies(1); });
    document.getElementById('btnReset').addEventListener('click', () => { filterForm.reset(); loadTenancies(1); });

    // ---------- Check-in ----------
    async function openCheckin() {
        clearFieldErrors(checkinForm);
        document.getElementById('modalAlert').innerHTML = '';
        checkinForm.reset();
        checkinForm.start_date.value = today();
        checkinForm.user_id.innerHTML = '<option value="">Memuat...</option>';
        checkinForm.room_id.innerHTML = '<option value="">Memuat...</option>';
        checkinModal.show();

        try {
            const [tenants, rooms] = await Promise.all([
                api('/tenants', { params: { has_room: 0, per_page: 50 } }),
                api('/rooms', { params: { status: 'available', per_page: 50 } }),
            ]);

            checkinForm.user_id.innerHTML = tenants.data.length
                ? '<option value="">-- Pilih penghuni --</option>' + tenants.data.map((u) =>
                    '<option value="' + u.id + '">' + esc(u.name) + ' (' + esc(u.email) + ')</option>').join('')
                : '<option value="">Tidak ada penghuni tanpa kamar</option>';

            checkinForm.room_id.innerHTML = rooms.data.length
                ? '<option value="">-- Pilih kamar --</option>' + rooms.data.map((r) =>
                    '<option value="' + r.id + '">Kamar ' + esc(r.number) + ' - ' + esc(r.type) + ' - ' + esc(rupiah(r.price)) + '</option>').join('')
                : '<option value="">Tidak ada kamar tersedia</option>';
        } catch (err) {
            showError(err, null, document.getElementById('modalAlert'));
        }
    }

    document.getElementById('btnCheckin').addEventListener('click', openCheckin);

    checkinForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearFieldErrors(checkinForm);
        const btn = document.getElementById('btnSaveCheckin');
        btn.disabled = true;

        const num = (v) => (v === '' ? null : Number(v));
        const payload = {
            user_id: num(checkinForm.user_id.value),
            room_id: num(checkinForm.room_id.value),
            start_date: checkinForm.start_date.value || null,
        };

        try {
            const res = await api('/tenancies', { method: 'POST', body: payload });
            checkinModal.hide();
            showAlert('success', res.message);
            loadTenancies(1);
        } catch (err) {
            showError(err, checkinForm, document.getElementById('modalAlert'));
        } finally {
            btn.disabled = false;
        }
    });

    // ---------- Aksi baris: identitas & check-out ----------
    body.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const t = state.rows.get(Number(btn.dataset.id));
        if (!t) return;

        if (btn.dataset.action === 'identity') {
            showIdentity(t);
        } else {
            state.checkoutId = t.id;
            document.getElementById('checkoutText').innerHTML =
                'Check-out <strong>' + esc(t.user ? t.user.name : '-') + '</strong> dari kamar <strong>' +
                esc(t.room ? t.room.number : '-') + '</strong>? Semua tagihan harus sudah lunas.';
            checkoutModal.show();
        }
    });

    async function showIdentity(t) {
        const box = document.getElementById('identityBody');
        document.getElementById('identityTitle').textContent = 'Identitas Penghuni Kamar ' + (t.room ? t.room.number : '');
        box.innerHTML = '<span class="text-muted">Memuat...</span>';
        identityModal.show();

        try {
            const res = await api('/rooms/' + t.room_id + '/tenant');
            const d = res.data;
            const row = (label, value) =>
                '<dt class="col-sm-4">' + label + '</dt><dd class="col-sm-8">' + esc(value || '-') + '</dd>';
            box.innerHTML = '<dl class="row mb-0">' +
                row('Nama', d.name) + row('Email', d.email) + row('No. HP', d.phone) +
                row('NIK', d.identity_number) + row('Alamat', d.address) +
                row('Tanggal masuk', fmtDate(d.start_date)) + '</dl>';
        } catch (err) {
            box.innerHTML = '<div class="text-danger">' + esc(err.message) + '</div>';
        }
    }

    document.getElementById('btnConfirmCheckout').addEventListener('click', async () => {
        const btn = document.getElementById('btnConfirmCheckout');
        btn.disabled = true;

        try {
            const res = await api('/tenancies/' + state.checkoutId + '/checkout', { method: 'PATCH' });
            checkoutModal.hide();
            showAlert('success', res.message);
            loadTenancies(state.page);
        } catch (err) {
            checkoutModal.hide();
            showError(err); // contoh: 422 masih ada tagihan belum lunas
        } finally {
            btn.disabled = false;
        }
    });

    loadRoomFilter();
    loadTenancies(1);
})();
</script>
@endpush