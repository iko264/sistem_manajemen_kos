<?php

// File ini hanya memuat file route per modul. Prefix '/api' ditambahkan oleh bootstrap/app.php.
// Pemilik: auth.php, rooms.php = A | tenancies.php = B | billing.php, dashboard.php = C
foreach (['auth', 'rooms', 'tenancies', 'billing', 'dashboard'] as $module) {
    require __DIR__."/api/{$module}.php";
}
