<?php

// File ini hanya memuat semua file di routes/web/*.php (urut abjad).
foreach (glob(__DIR__.'/web/*.php') as $file) {
    require $file;
}
