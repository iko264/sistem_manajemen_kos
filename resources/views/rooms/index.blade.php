@extends('layouts.app')

@section('title', 'Kamar')

@section('content')
<div class="page-head">
    <div>
        <h1 class="h4"><span class="page-icon"><i class="bi bi-door-open"></i></span>Daftar Kamar</h1>
        <p class="page-sub">Pantau status, tipe, dan harga seluruh kamar.</p>
    </div>
    <button type="button" class="btn btn-primary admin-only d-none" id="btnAdd"><i class="bi bi-plus-lg me-1"></i>Tambah Kamar</button>
</div>

<form id="filterForm" class="card card-body shadow-sm mb-3" novalidate>
    <div class="row g-2">
        <div class="col-6 col-md-3">
            <input type="text" class="form-control" name="search" placeholder="Cari nomor kamar">
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="status">
                <option value="">Semua status</option>
                <option value="available">Tersedia</option>
                <option value="occupied">Terisi</option>
                <option value="maintenance">Perbaikan</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="type">
                <option value="">Semua tipe</option>
                <option value="standard">Standard</option>
                <option value="deluxe">Deluxe</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <input type="number" min="0" class="form-control" name="min_price" placeholder="Harga min">
        </div>
        <div class="col-6 col-md-2">
            <input type="number" min="0" class="form-control" name="max_price" placeholder="Harga maks">
        </div>
        <div class="col-6 col-md-1">
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
                    <th>No. Kamar</th>
                    <th>Tipe</th>
                    <th>Harga / bulan</th>
                    <th>Status</th>
                    <th>Deskripsi</th>
                    <th class="admin-only d-none text-end">Aksi</th>
                </tr>
            </thead>
            <tbody id="roomsBody"></tbody>
        </table>
    </div>
    <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
        <small class="text-muted" id="pagerInfo"></small>
        <nav aria-label="Navigasi halaman"><ul class="pagination pagination-sm mb-0" id="pager"></ul></nav>
    </div>
</div>

{{-- Modal tambah / edit --}}
<div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="roomForm" novalidate>
            <div class="modal-header">
                <h2 class="modal-title h5" id="roomModalTitle">Tambah Kamar</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="modalAlert"></div>
                <div class="mb-3">
                    <label class="form-label" for="f_number">Nomor kamar</label>
                    <input type="text" class="form-control" id="f_number" name="number" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label" for="f_type">Tipe</label>
                        <select class="form-select" id="f_type" name="type">
                            <option value="standard">Standard</option>
                            <option value="deluxe">Deluxe</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="f_status">Status</label>
                        <select class="form-select" id="f_status" name="status"></select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="f_price">Harga per bulan (Rp)</label>
                    <input type="number" min="1" class="form-control" id="f_price" name="price" required>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="f_description">Deskripsi</label>
                    <textarea class="form-control" id="f_description" name="description" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSave">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal konfirmasi hapus --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5">Hapus Kamar</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">Hapus kamar <strong id="deleteLabel"></strong>? Tindakan ini tidak dapat dibatalkan.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete">Hapus</button>
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
        available:   { label: 'Tersedia',  cls: 'badge-soft-success', icon: 'bi-check-circle' },
        occupied:    { label: 'Terisi',    cls: 'badge-soft-danger', icon: 'bi-person-fill' },
        maintenance: { label: 'Perbaikan', cls: 'badge-soft-warning', icon: 'bi-tools' },
    };

    const isAdmin = Auth.isAdmin();
    const state = { page: 1, rooms: new Map(), editingId: null, deletingId: null };

    const filterForm = document.getElementById('filterForm');
    const roomForm = document.getElementById('roomForm');
    const body = document.getElementById('roomsBody');
    const roomModal = new bootstrap.Modal('#roomModal');
    const deleteModal = new bootstrap.Modal('#deleteModal');
    const colspan = isAdmin ? 6 : 5;

    document.querySelectorAll('.admin-only').forEach((el) => el.classList.toggle('d-none', !isAdmin));

    // ---------- Daftar ----------
    async function loadRooms(page) {
        state.page = page || 1;
        clearFieldErrors(filterForm);

        const params = { page: state.page };
        new FormData(filterForm).forEach((v, k) => { if (v !== '') params[k] = v; });

        body.innerHTML = '<tr><td colspan="' + colspan + '" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat...</td></tr>';

        try {
            const res = await api('/rooms', { params });
            renderRows(res.data);
            renderPager(res.meta);
        } catch (err) {
            body.innerHTML = '<tr><td colspan="' + colspan + '" class="text-center text-danger py-4">Gagal memuat data.</td></tr>';
            document.getElementById('pager').innerHTML = '';
            document.getElementById('pagerInfo').textContent = '';
            showError(err, filterForm);
        }
    }

    function renderRows(rooms) {
        state.rooms.clear();

        if (!rooms.length) {
            body.innerHTML = '<tr><td colspan="' + colspan + '" class="text-center empty-state"><i class="bi bi-inbox"></i>Tidak ada kamar yang cocok.</td></tr>';
            return;
        }

        body.innerHTML = rooms.map((r) => {
            state.rooms.set(r.id, r);
            const st = STATUS[r.status] || { label: r.status, cls: 'bg-secondary' };
            const actions = isAdmin
                ? '<td class="text-end text-nowrap cell-actions">' +
                  '<button type="button" class="btn btn-sm btn-outline-primary me-1" data-action="edit" data-id="' + r.id + '">Edit</button>' +
                  '<button type="button" class="btn btn-sm btn-outline-danger" data-action="delete" data-id="' + r.id + '">Hapus</button></td>'
                : '';
            return '<tr>' +
                                '<td data-label="No. Kamar" class="fw-semibold">' + esc(r.number) + '</td>' +
                '<td data-label="Tipe" class="text-capitalize">' + esc(r.type) + '</td>' +
                '<td data-label="Harga / bulan">' + esc(rupiah(r.price)) + '</td>' +
                '<td data-label="Status"><span class="badge badge-soft ' + st.cls + '"><i class="bi ' + (st.icon || 'bi-circle') + '"></i>' + esc(st.label) + '</span></td>' +
                '<td data-label="Deskripsi" class="text-muted small">' + esc(r.description || '-') + '</td>' +
                actions + '</tr>';
        }).join('');
    }

    function renderPager(meta) {
        document.getElementById('pagerInfo').textContent =
            'Halaman ' + meta.current_page + ' dari ' + meta.last_page + ' (total ' + meta.total + ' kamar)';

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
        loadRooms(Number(link.dataset.page));
    });

    filterForm.addEventListener('submit', (e) => { e.preventDefault(); loadRooms(1); });
    document.getElementById('btnReset').addEventListener('click', () => { filterForm.reset(); loadRooms(1); });

    // ---------- Tambah / edit ----------
    function setStatusOptions(current) {
        const sel = roomForm.status;
        sel.innerHTML = '<option value="available">Tersedia</option><option value="maintenance">Perbaikan</option>' +
            (current === 'occupied' ? '<option value="occupied">Terisi (diatur otomatis saat check-in)</option>' : '');
        sel.value = current || 'available';
        sel.disabled = current === 'occupied'; // status 'occupied' hanya berubah lewat check-in/check-out
    }

    function openForm(room) {
        clearFieldErrors(roomForm);
        document.getElementById('modalAlert').innerHTML = '';
        roomForm.reset();
        state.editingId = room ? room.id : null;
        document.getElementById('roomModalTitle').textContent = room ? 'Edit Kamar ' + room.number : 'Tambah Kamar';

        setStatusOptions(room ? room.status : 'available');
        if (room) {
            roomForm.number.value = room.number;
            roomForm.type.value = room.type;
            roomForm.price.value = room.price;
            roomForm.description.value = room.description || '';
        }
        roomModal.show();
    }

    document.getElementById('btnAdd').addEventListener('click', () => openForm(null));

    roomForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearFieldErrors(roomForm);
        const btn = document.getElementById('btnSave');
        btn.disabled = true;

        const payload = {
            number: roomForm.number.value.trim(),
            type: roomForm.type.value,
            price: roomForm.price.value === '' ? null : Number(roomForm.price.value),
            status: roomForm.status.value,
            description: roomForm.description.value.trim() || null,
        };

        try {
            const editing = state.editingId !== null;
            const res = await api(editing ? '/rooms/' + state.editingId : '/rooms', {
                method: editing ? 'PUT' : 'POST',
                body: payload,
            });
            roomModal.hide();
            showAlert('success', res.message);
            loadRooms(editing ? state.page : 1);
        } catch (err) {
            showError(err, roomForm, document.getElementById('modalAlert'));
        } finally {
            btn.disabled = false;
        }
    });

    // ---------- Hapus ----------
    body.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const room = state.rooms.get(Number(btn.dataset.id));
        if (!room) return;

        if (btn.dataset.action === 'edit') {
            openForm(room);
        } else {
            state.deletingId = room.id;
            document.getElementById('deleteLabel').textContent = room.number;
            deleteModal.show();
        }
    });

    document.getElementById('btnConfirmDelete').addEventListener('click', async () => {
        const btn = document.getElementById('btnConfirmDelete');
        btn.disabled = true;
        const wasLastOnPage = state.rooms.size === 1 && state.page > 1;

        try {
            const res = await api('/rooms/' + state.deletingId, { method: 'DELETE' });
            deleteModal.hide();
            showAlert('success', res.message);
            loadRooms(wasLastOnPage ? state.page - 1 : state.page);
        } catch (err) {
            deleteModal.hide();
            showError(err); // contoh: 409 kamar terisi / punya riwayat penghunian
        } finally {
            btn.disabled = false;
        }
    });

    loadRooms(1);
})();
</script>
@endpush
