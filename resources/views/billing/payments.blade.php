@extends('layouts.app')
@section('title', 'Verifikasi Pembayaran')
@section('content')
<h4 class="mb-3">Verifikasi Pembayaran</h4>
<form id="filter" class="row g-2 mb-3">
    <div class="col-8 col-md-3">
        <select name="status" class="form-select">
            <option value="pending" selected>Menunggu verifikasi</option><option value="approved">Disetujui</option>
            <option value="rejected">Ditolak</option><option value="">Semua</option>
        </select>
    </div>
    <div class="col-4 col-md-2 d-grid"><button class="btn btn-outline-secondary">Filter</button></div>
</form>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Diunggah</th><th>Penghuni</th><th>Kamar</th><th>Periode</th><th>Jumlah</th><th>Bukti</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
        <tbody id="rows"><tr><td colspan="8" class="text-center text-muted py-4">Memuat...</td></tr></tbody>
    </table>
</div></div>
<div class="mt-3" id="pagination"></div>

<div class="modal fade" id="verifyModal" tabindex="-1"><div class="modal-dialog">
    <form class="modal-content" id="verify-form" novalidate>
        <div class="modal-header"><h5 class="modal-title" id="verify-title"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label" id="note-label">Catatan</label><textarea name="note" class="form-control" rows="3"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn" id="verify-submit"></button></div>
    </form>
</div></div>
@endsection

@push('scripts')
<script>
const ST = { pending: ['warning text-dark', 'Menunggu'], approved: ['success', 'Disetujui'], rejected: ['danger', 'Ditolak'] };
const vModal = new bootstrap.Modal(document.getElementById('verifyModal'));
const vForm = document.getElementById('verify-form');
let page = 1, items = [], current = null, action = null;

async function load() {
    const q = Object.fromEntries(new FormData(document.getElementById('filter'))); q.page = page;
    try {
        const res = await Api.get('/api/payments', q);
        items = res.data; render();
        Api.renderPagination(document.getElementById('pagination'), res.meta, (p) => { page = p; load(); });
    } catch (err) { Api.handleError(err); }
}

function render() {
    const tbody = document.getElementById('rows');
    if (!items.length) { tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Tidak ada pembayaran.</td></tr>'; return; }
    tbody.innerHTML = items.map((p) => {
        const [cls, label] = ST[p.status] || ['secondary', p.status];
        const t = p.invoice?.tenancy;
        return `<tr>
            <td class="small">${new Date(p.created_at).toLocaleString('id-ID')}</td>
            <td>${Api.esc(t?.user?.name)}</td><td>${Api.esc(t?.room?.number)}</td><td>${Api.esc(p.invoice?.period)}</td>
            <td>${Api.rupiah(p.amount)}</td>
            <td>${p.proof_url ? `<a href="${Api.esc(p.proof_url)}" target="_blank" rel="noopener">Lihat</a>` : '-'}</td>
            <td><span class="badge text-bg-${cls}">${label}</span>${p.note ? `<div class="small text-muted">${Api.esc(p.note)}</div>` : ''}</td>
            <td class="text-end text-nowrap">${p.status === 'pending' ? `
                <button class="btn btn-sm btn-success" data-approve="${p.id}">Setujui</button>
                <button class="btn btn-sm btn-danger" data-reject="${p.id}">Tolak</button>` : ''}</td>
        </tr>`;
    }).join('');
    tbody.querySelectorAll('[data-approve]').forEach((b) => b.addEventListener('click', () => openVerify(b.dataset.approve, 'approve')));
    tbody.querySelectorAll('[data-reject]').forEach((b) => b.addEventListener('click', () => openVerify(b.dataset.reject, 'reject')));
}

function openVerify(id, act) {
    current = items.find((p) => p.id == id); action = act;
    Api.clearErrors(vForm); vForm.reset();
    const approve = act === 'approve';
    document.getElementById('verify-title').textContent = (approve ? 'Setujui' : 'Tolak') + ` pembayaran ${current.invoice?.period}`;
    document.getElementById('note-label').textContent = approve ? 'Catatan (opsional)' : 'Alasan penolakan (wajib)';
    const btn = document.getElementById('verify-submit');
    btn.textContent = approve ? 'Setujui' : 'Tolak'; btn.className = 'btn ' + (approve ? 'btn-success' : 'btn-danger');
    vModal.show();
}

vForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
        const res = await Api.patch(`/api/payments/${current.id}/verify`, { action, note: vForm.note.value });
        vModal.hide(); Api.alert('success', res.message); load();
    } catch (err) { Api.handleError(err, vForm); }
});

document.getElementById('filter').addEventListener('submit', (e) => { e.preventDefault(); page = 1; load(); });
document.addEventListener('api:ready', () => {
    if (!Api.isAdmin()) { window.location.href = '/invoices'; return; }
    load();
});
</script>
@endpush
