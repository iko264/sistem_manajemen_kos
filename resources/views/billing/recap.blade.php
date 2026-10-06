@extends('layouts.app')
@section('title', 'Rekap Tagihan')
@section('content')
<h4 class="mb-3">Rekap Tagihan</h4>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-tenants">Status Bayar Penghuni</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-rooms">Tagihan per Kamar</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-tenants">
        <form id="f-tenants" class="row g-2 mb-3">
            <div class="col-6 col-md-3"><input type="month" name="period" class="form-control" placeholder="YYYY-MM"></div>
            <div class="col-6 col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua status</option><option value="paid">Lunas</option><option value="unpaid">Belum dibayar</option>
                    <option value="pending">Menunggu verifikasi</option><option value="overdue">Terlambat</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-grid"><button class="btn btn-outline-secondary">Filter</button></div>
        </form>
        <div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Penghuni</th><th>Kamar</th><th>Periode</th><th>Jumlah</th><th>Jatuh tempo</th><th>Status</th></tr></thead>
            <tbody id="rows-tenants"></tbody></table></div></div>
        <div class="mt-3" id="pg-tenants"></div>
    </div>

    <div class="tab-pane fade" id="tab-rooms">
        <form id="f-rooms" class="row g-2 mb-3">
            <div class="col-6 col-md-3"><input type="month" name="period" class="form-control" placeholder="YYYY-MM (kosong = semua)"></div>
            <div class="col-12 col-md-2 d-grid"><button class="btn btn-outline-secondary">Filter</button></div>
        </form>
        <div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Kamar</th><th>Tipe</th><th class="text-end">Jml Tagihan</th><th class="text-end">Total</th><th class="text-end">Terbayar</th><th class="text-end">Belum Lunas</th><th class="text-end">Terlambat</th></tr></thead>
            <tbody id="rows-rooms"></tbody></table></div></div>
        <div class="mt-3" id="pg-rooms"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const fmtDate = (d) => d ? new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
const ST = { paid: ['success', 'Lunas'], unpaid: ['danger', 'Belum dibayar'], pending: ['warning text-dark', 'Menunggu verifikasi'], no_invoice: ['secondary', 'Belum ada tagihan'] };
const state = { tenants: 1, rooms: 1 };
const form = (id) => Object.fromEntries(new FormData(document.getElementById(id)));

async function loadTenants() {
    try {
        const res = await Api.get('/api/billing/tenants', { ...form('f-tenants'), page: state.tenants });
        document.getElementById('rows-tenants').innerHTML = res.data.length ? res.data.map((t) => {
            const [cls, label] = ST[t.payment_status] || ['secondary', t.payment_status];
            return `<tr><td><div class="fw-semibold">${Api.esc(t.user.name)}</div><div class="small text-muted">${Api.esc(t.user.email)}</div></td>
                <td>${Api.esc(t.room.number)}</td><td>${Api.esc(t.period)}</td>
                <td>${t.invoice ? Api.rupiah(t.invoice.amount) : '-'}</td><td>${fmtDate(t.invoice?.due_date)}</td>
                <td><span class="badge text-bg-${cls}">${label}</span>${t.is_overdue ? '<span class="badge text-bg-dark ms-1">Terlambat</span>' : ''}</td></tr>`;
        }).join('') : '<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data.</td></tr>';
        Api.renderPagination(document.getElementById('pg-tenants'), res.meta, (p) => { state.tenants = p; loadTenants(); });
    } catch (err) { Api.handleError(err); }
}

async function loadRooms() {
    try {
        const res = await Api.get('/api/billing/rooms', { ...form('f-rooms'), page: state.rooms });
        document.getElementById('rows-rooms').innerHTML = res.data.length ? res.data.map((r) => `<tr>
            <td class="fw-semibold">${Api.esc(r.number)}</td><td class="text-capitalize">${Api.esc(r.type)}</td>
            <td class="text-end">${r.total_invoices}</td><td class="text-end">${Api.rupiah(r.total_amount)}</td>
            <td class="text-end text-success">${Api.rupiah(r.total_paid)}</td><td class="text-end text-danger">${Api.rupiah(r.total_unpaid)}</td>
            <td class="text-end">${r.overdue_count ? `<span class="badge text-bg-dark">${r.overdue_count}</span>` : 0}</td></tr>`).join('')
            : '<tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data.</td></tr>';
        Api.renderPagination(document.getElementById('pg-rooms'), res.meta, (p) => { state.rooms = p; loadRooms(); });
    } catch (err) { Api.handleError(err); }
}

document.getElementById('f-tenants').addEventListener('submit', (e) => { e.preventDefault(); state.tenants = 1; loadTenants(); });
document.getElementById('f-rooms').addEventListener('submit', (e) => { e.preventDefault(); state.rooms = 1; loadRooms(); });
document.addEventListener('api:ready', () => {
    if (!Api.isAdmin()) { window.location.href = '/invoices'; return; }
    document.getElementById('f-tenants').period.value = new Date().toISOString().slice(0, 7);
    loadTenants(); loadRooms();
});
</script>
@endpush
