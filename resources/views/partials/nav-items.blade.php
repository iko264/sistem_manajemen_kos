{{-- Daftar menu navbar. Menu ditampilkan sesuai role user yang login.
     Pemilik bagian B/C menambahkan menunya di sini, contoh:
     { label: 'Penghuni', href: '/tenancies', roles: ['admin', 'tenant'] }, --}}
<script>
    window.NAV_ITEMS = [
        { label: 'Kamar', href: '/rooms', roles: ['admin', 'tenant'] },
    ];
</script>
