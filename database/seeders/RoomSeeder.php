<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        // Lantai 1: standard, Lantai 2: deluxe. Status 'occupied' diatur oleh proses check-in (bagian B).
        for ($i = 1; $i <= 5; $i++) {
            Room::updateOrCreate(['number' => '10'.$i], [
                'type' => 'standard',
                'price' => 750000,
                'status' => Room::STATUS_AVAILABLE,
                'description' => 'Kamar standard lantai 1, kasur, lemari, dan kamar mandi luar.',
            ]);

            Room::updateOrCreate(['number' => '20'.$i], [
                'type' => 'deluxe',
                'price' => 1200000,
                'status' => $i === 5 ? Room::STATUS_MAINTENANCE : Room::STATUS_AVAILABLE,
                'description' => 'Kamar deluxe lantai 2, AC, kamar mandi dalam, dan meja kerja.',
            ]);
        }
    }
}
