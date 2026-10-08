{{-- Daftar menu navbar. Menu ditampilkan sesuai role user yang login.
     Pemilik bagian B/C menambahkan menunya di sini, contoh:
     { label: 'Penghuni', href: '/tenancies', icon: 'bi-people', roles: ['admin', 'tenant'] },
     'icon' opsional (nama kelas Bootstrap Icons). --}}
<script>
    window.NAV_ITEMS = [
        { label: 'Dashboard', href: '/dashboard', icon: 'bi-speedometer2', roles: ['admin'] },
        { label: 'Kamar', href: '/rooms', icon: 'bi-door-open', roles: ['admin', 'tenant'] },
        { label: 'Penghuni', href: '/tenancies', icon: 'bi-people', roles: ['admin', 'tenant'] },
        { label: 'Tagihan', href: '/invoices', icon: 'bi-receipt', roles: ['admin', 'tenant'] },
        { label: 'Pembayaran', href: '/payments', icon: 'bi-credit-card', roles: ['admin'] },
        { label: 'Rekap', href: '/billing', icon: 'bi-bar-chart-line', roles: ['admin'] },
    ];
</script>