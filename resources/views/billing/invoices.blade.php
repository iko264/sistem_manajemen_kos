@extends('layouts.app')
@section('title', 'Tagihan')
@section('content')
<div class="page-head">
    <div>
        <h1 class="h4"><span class="page-icon"><i class="bi bi-receipt"></i></span><span id="page-title">Tagihan Saya</span></h1>
        <p class="page-sub">Pantau status, jatuh tempo, dan pembayaran tagihan.</p>
    </div>
    <button class="btn btn-primary d-none admin-only" id="btn-generate"><i class="bi bi-lightning-charge me-1"></i>Generate Tagihan</button>
</div>

<form id="filter" class="card card-body mb-3">
    <div class="row g-2 align-items-center">
        <div class="col-6 col-md-3">
            <select name="status" class="form-select">
                <option value="">Semua status</option><option value="unpaid">Belum dibayar</option>
                <option value="pending">Menunggu verifikasi</option><option value="paid">Lunas</option>
            </select>
        </div>
        <div class="col-6 col-md-2"><input type="month" name="period" class="form-control" placeholder="YYYY-MM"></div>
        <div class="col-6 col-md-3 d-none admin-only"><select name="room_id" class="form-select" id="filter-room"><option value="">Semua kamar</option></select></div>
        <div class="col-6 col-md-2">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="overdue" value="1" id="f-overdue"><label class="form-check-label" for="f-overdue">Terlambat saja</label></div>
        </div>
        <div class="col-12 col-md-2 d-grid"><button class="btn btn-dark"><i class="bi bi-funnel me-1"></i>Filter</button></div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-stack">
            <thead class="table-light"><tr>
                <th>Periode</th><th class="d-none admin-only">Penghuni</th><th>Kamar</th><th>Jumlah</th><th>Jatuh tempo</th><th>Status</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody id="rows"><tr><td colspan="7" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat...</td></tr></tbody>
        </table>
    </div>
    <div class="card-footer" id="pagination"></div>
</div>

{{-- Modal upload bukti (tenant) --}}
<div class="modal fade" id="payModal" tabindex="-1"><div class="modal-dialog">
    <form class="modal-content" id="pay-form" novalidate>
        <div class="modal-header"><h5 class="modal-title" id="pay-title">Bayar Tagihan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="alert alert-danger d-none" id="pay-error"></div>
            <input type="hidden" name="invoice_id">
            <div class="mb-3"><label class="form-label">Jumlah dibayar (Rp)</label><input type="number" name="amount" class="form-control" min="1"></div>
            <div class="mb-3"><label class="form-label">Bukti pembayaran (JPG/PNG, maks 2MB)</label><input type="file" name="proof" class="form-control" accept="image/jpeg,image/png"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="pay-submit"><i class="bi bi-upload me-1"></i>Kirim Bukti</button></div>
    </form>
</div></div>

{{-- Modal generate (admin) --}}
<div class="modal fade" id="genModal" tabindex="-1"><div class="modal-dialog">
    <form class="modal-content" id="gen-form" novalidate>
        <div class="modal-header"><h5 class="modal-title">Generate Tagihan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Periode (YYYY-MM)</label><input type="month" name="period" class="form-control" placeholder="YYYY-MM"></div>
            <p class="small text-muted mb-0">Dibuat untuk semua hunian aktif yang belum punya tagihan periode ini. Jatuh tempo: tanggal 10.</p>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary"><i class="bi bi-lightning-charge me-1"></i>Generate</button></div>
    </form>
</div></div>
@endsection

@push('scripts')
<script>
const ST = {
    unpaid: ['badge-soft-danger', 'Belum dibayar', 'bi-x-circle'],
    pending: ['badge-soft-warning', 'Menunggu verifikasi', 'bi-hourglass-split'],
    paid: ['badge-soft-success', 'Lunas', 'bi-check-circle'],
};
const fmtDate = (d) => d ? new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
const payModal = new bootstrap.Modal(document.getElementById('payModal'));
const genModal = new bootstrap.Modal(document.getElementById('genModal'));
const payForm = document.getElementById('pay-form'), genForm = document.getElementById('gen-form');
let page = 1, items = [];

async function load() {
    const q = Object.fromEntries(new FormData(document.getElementById('filter')));
    q.page = page;
    try {
        const res = await Api.get('/api/invoices', q);
        items = res.data; render();
        Api.renderPagination(document.getElementById('pagination'), res.meta, (p) => { page = p; load(); });
    } catch (err) { Api.handleError(err); }
}

function render() {
    const admin = Api.isAdmin(), tbody = document.getElementById('rows');
    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center empty-state"><i class="bi bi-receipt"></i>Tidak ada tagihan.</td></tr>';
        return;
    }
    tbody.innerHTML = items.map((i) => {
        const [cls, label, icon] = ST[i.status] || ['badge-soft-secondary', i.status, 'bi-circle'];
        const rejected = (i.payments || []).filter((p) => p.status === 'rejected').slice(-1)[0];
        const rejNote = i.status === 'unpaid' && rejected
            ? `<div class="small text-danger mt-1">Ditolak: ${Api.esc(rejected.note)}</div>` : '';
        return `<tr>
            <td data-label="Periode" class="fw-semibold">${Api.esc(i.period)}</td>
            ${admin ? `<td data-label="Penghuni">${Api.esc(i.tenancy?.user?.name)}</td>` : ''}
            <td data-label="Kamar">${Api.esc(i.tenancy?.room?.number)}</td>
            <td data-label="Jumlah">${Api.rupiah(i.amount)}</td>
            <td data-label="Jatuh tempo">${fmtDate(i.due_date)}</td>
            <td data-label="Status"><span class="badge badge-soft ${cls}"><i class="bi ${icon}"></i>${label}</span>
                ${i.is_overdue ? '<span class="badge badge-soft badge-soft-danger ms-1"><i class="bi bi-exclamation-triangle"></i>Terlambat</span>' : ''}${rejNote}</td>
            <td data-label="Aksi" class="text-end cell-actions">${!admin && i.status === 'unpaid' ? `<button class="btn btn-sm btn-primary" data-pay="${i.id}"><i class="bi bi-wallet2 me-1"></i>Bayar</button>` : ''}</td>
        </tr>`;
    }).join('');
    tbody.querySelectorAll('[data-pay]').forEach((b) => b.addEventListener('click', () => openPay(items.find((i) => i.id == b.dataset.pay))));
}

function openPay(inv) {
    Api.clearErrors(payForm); payForm.reset();
    document.getElementById('pay-error').classList.add('d-none');
    document.getElementById('pay-title').textContent = 'Bayar Tagihan ' + inv.period;
    payForm.elements['invoice_id'].value = inv.id;
    payForm.elements['amount'].value = inv.amount;
    payModal.show();
}

payForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('pay-submit'), box = document.getElementById('pay-error');
    const fd = new FormData(payForm); const id = fd.get('invoice_id'); fd.delete('invoice_id');
    btn.disabled = true; box.classList.add('d-none');
    try {
        const res = await Api.upload(`/api/invoices/${id}/payments`, fd);
        payModal.hide(); Api.alert('success', res.message); load();
    } catch (err) {
        if (err.status === 422) Api.showErrors(payForm, err);
        box.textContent = err.message; box.classList.remove('d-none');
    } finally { btn.disabled = false; }
});

genForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
        const res = await Api.post('/api/invoices/generate', { period: genForm.period.value });
        genModal.hide();
        Api.alert('success', `${res.message} (dilewati: ${res.data.skipped})`); load();
    } catch (err) { Api.handleError(err, genForm); }
});

document.getElementById('filter').addEventListener('submit', (e) => { e.preventDefault(); page = 1; load(); });
document.getElementById('btn-generate').addEventListener('click', () => {
    Api.clearErrors(genForm); genForm.period.value = new Date().toISOString().slice(0, 7); genModal.show();
});

document.addEventListener('api:ready', async () => {
    // dukung tautan dari dashboard: /invoices?status=unpaid&overdue=1
    const qs = new URLSearchParams(location.search), f = document.getElementById('filter');
    if (qs.get('status')) f.status.value = qs.get('status');
    if (qs.get('overdue') === '1') f.overdue.checked = true;

    if (Api.isAdmin()) {
        document.querySelectorAll('.admin-only').forEach((el) => el.classList.remove('d-none'));
        document.getElementById('page-title').textContent = 'Semua Tagihan';
        try {
            const rooms = await Api.get('/api/rooms', { per_page: 50 });
            document.getElementById('filter-room').insertAdjacentHTML('beforeend',
                rooms.data.map((r) => `<option value="${r.id}">${Api.esc(r.number)}</option>`).join(''));
        } catch (err) { /* opsional */ }
    }
    load();
});
</script>
@endpush