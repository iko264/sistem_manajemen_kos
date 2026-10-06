<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 'role' tidak fillable, jadi seeder memakai unguarded().
        User::unguarded(function () {
            User::updateOrCreate(['email' => 'admin@kos.test'], [
                'name' => 'Admin Kos',
                'password' => 'password', // di-hash otomatis oleh cast 'hashed'
                'role' => User::ROLE_ADMIN,
                'phone' => '081200000000',
                'address' => 'Jl. Kos Sejahtera No. 1',
            ]);

            $tenants = ['Budi Santoso', 'Siti Aminah', 'Andi Pratama', 'Dewi Lestari', 'Rizky Ramadhan'];

            foreach ($tenants as $i => $name) {
                $n = $i + 1;

                User::updateOrCreate(['email' => "tenant{$n}@kos.test"], [
                    'name' => $name,
                    'password' => 'password',
                    'role' => User::ROLE_TENANT,
                    'phone' => '08123456'.sprintf('%04d', $n),
                    'identity_number' => '3302010101900'.sprintf('%03d', $n), // NIK dummy 16 digit
                    'address' => "Jl. Contoh No. {$n}, Purwokerto",
                ]);
            }
        });
    }
}
