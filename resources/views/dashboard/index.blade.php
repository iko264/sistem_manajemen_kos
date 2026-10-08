@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="page-head">
    <div>
        <h1 class="h4"><span class="page-icon"><i class="bi bi-speedometer2"></i></span>Dashboard</h1>
        <p class="page-sub" id="greet">Ringkasan operasional kos.</p>
    </div>
</div>

<div class="row g-3 mb-4" id="cards"></div>

<div class="card">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
        <span class="fw-semibold"><i class="bi bi-hourglass-split me-2 text-primary"></i>Menunggu Verifikasi
            <span class="badge badge-soft badge-soft-warning ms-1" id="pending-count">0</span></span>
        <a href="/payments" class="btn btn-sm btn-outline-primary"><i class="bi bi-patch-check me-1"></i>Verifikasi</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0 table-stack">
            <thead class="table-light"><tr><th>Penghuni</th><th>Kamar</th><th>Periode</th><th>Jumlah</th><th>Diunggah</th></tr></thead>
            <tbody id="pending-rows"><tr><td colspan="5" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat...</td></tr></tbody>
        </table>
    </div>
    <div class="card-footer small text-muted">Menampilkan maksimal 5 pembayaran terbaru yang belum diverifikasi.</div>
</div>
@endsection

@push('scripts')
<script>
const card = (label, value, cls, icon, href) => {
    const body = `<div class="card stat-card h-100"><div class="card-body d-flex align-items-center gap-3 stat-${cls}">
        <span class="stat-icon"><i class="bi ${icon}"></i></span>
        <div><div class="stat-label">${label}</div><div class="stat-value">${value}</div></div></div></div>`;
    return `<div class="col-6 col-md-4 col-xl-3">${href ? `<a class="stat-link" href="${href}">${body}</a>` : body}</div>`;
};

// Skeleton selama data dimuat
document.getElementById('cards').innerHTML = Array(10).fill(
    '<div class="col-6 col-md-4 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="skeleton" style="height:48px"></div></div></div></div>'
).join('');

document.addEventListener('api:ready', async () => {
    if (!Api.isAdmin()) { window.location.href = '/invoices'; return; }

    const u = Auth.user();
    if (u && u.name) document.getElementById('greet').textContent = 'Halo, ' + u.name + '. Ini ringkasan kos hari ini.';

    try {
        const d = (await Api.get('/api/admin/dashboard')).data;
        document.getElementById('cards').innerHTML = [
            card('Total Kamar', d.total_rooms, 'dark', 'bi-buildings', '/rooms'),
            card('Kamar Tersedia', d.available_rooms, 'success', 'bi-door-open'),
            card('Kamar Terisi', d.occupied_rooms, 'danger', 'bi-person-fill'),
            card('Kamar Perbaikan', d.maintenance_rooms, 'warning', 'bi-tools'),
            card('Penghuni Aktif', d.active_tenants, 'primary', 'bi-people', '/tenancies'),
            card('Tagihan Belum Dibayar', d.unpaid_count, 'danger', 'bi-receipt', '/invoices?status=unpaid'),
            card('Menunggu Verifikasi', d.pending_count, 'warning', 'bi-hourglass-split', '/payments'),
            card('Tagihan Terlambat', d.overdue_count, 'danger', 'bi-exclamation-triangle', '/invoices?overdue=1'),
            card('Total Belum Lunas', Api.rupiah(d.total_unpaid_amount), 'danger', 'bi-cash-stack'),
            card('Pendapatan Bulan Ini', Api.rupiah(d.revenue_this_month), 'success', 'bi-graph-up-arrow'),
        ].join('');

        document.getElementById('pending-count').textContent = d.pending_count;

        const rows = d.pending_payments;
        document.getElementById('pending-rows').innerHTML = rows.length ? rows.map((p) => {
            const name = (p.invoice && p.invoice.tenancy && p.invoice.tenancy.user && p.invoice.tenancy.user.name) || '-';
            const ini = String(name).trim().split(/\s+/).slice(0, 2).map((w) => w.charAt(0).toUpperCase()).join('');
            return `<tr>
                <td data-label="Penghuni"><div class="d-flex align-items-center gap-2 text-start"><span class="avatar-sm">${Api.esc(ini)}</span><span class="fw-semibold">${Api.esc(name)}</span></div></td>
                <td data-label="Kamar">${Api.esc(p.invoice?.tenancy?.room?.number)}</td>
                <td data-label="Periode">${Api.esc(p.invoice?.period)}</td>
                <td data-label="Jumlah">${Api.rupiah(p.amount)}</td>
                <td data-label="Diunggah" class="small text-muted">${new Date(p.created_at).toLocaleString('id-ID')}</td></tr>`;
        }).join('')
            : '<tr><td colspan="5" class="text-center empty-state"><i class="bi bi-patch-check"></i>Tidak ada pembayaran yang menunggu verifikasi.</td></tr>';
    } catch (err) {
        document.getElementById('cards').innerHTML = '';
        document.getElementById('pending-rows').innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Gagal memuat data.</td></tr>';
        Api.handleError(err);
    }
});
</script>
@endpush