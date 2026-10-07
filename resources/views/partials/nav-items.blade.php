{{-- Daftar menu navbar. Menu ditampilkan sesuai role user yang login.
     Pemilik bagian B/C menambahkan menunya di sini, contoh:
     { label: 'Penghuni', href: '/tenancies', icon: 'bi-people', roles: ['admin', 'tenant'] },
     'icon' opsional (nama kelas Bootstrap Icons). --}}
<script>
    window.NAV_ITEMS = [
        { label: 'Kamar', href: '/rooms', icon: 'bi-door-open', roles: ['admin', 'tenant'] },
        { label: 'Penghuni', href: '/tenancies', icon: 'bi-people', roles: ['admin', 'tenant'] },
    ];
</script>