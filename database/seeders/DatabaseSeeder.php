<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = [
            UserSeeder::class,
            RoomSeeder::class,
            TenancySeeder::class,   // milik B
            InvoiceSeeder::class,   // milik C
            PaymentSeeder::class,   // milik C
        ];

        foreach ($seeders as $seeder) {
            // Seeder B dan C baru dipanggil jika class-nya sudah ada (setelah di-merge).
            if (class_exists($seeder)) {
                $this->call($seeder);
            }
        }
    }
}
