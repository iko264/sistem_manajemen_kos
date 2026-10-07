<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenancySeeder extends Seeder
{
    public function run(): void
    {
        // Tenant1-3 (dari UserSeeder A) menempati kamar berbeda (dari RoomSeeder A).
        $assignments = [
            'tenant1@kos.test' => ['room' => '101', 'start_date' => '2026-09-01'],
            'tenant2@kos.test' => ['room' => '102', 'start_date' => '2026-09-01'],
            'tenant3@kos.test' => ['room' => '201', 'start_date' => '2026-09-01'],
        ];

        foreach ($assignments as $email => $data) {
            $user = User::where('email', $email)->first();
            $room = Room::where('number', $data['room'])->first();

            if (! $user || ! $room) {
                continue;
            }

            Tenancy::updateOrCreate(
                ['user_id' => $user->id, 'room_id' => $room->id, 'status' => 'active'],
                ['start_date' => $data['start_date']]
            );

            $room->update(['status' => Room::STATUS_OCCUPIED]);
        }
    }
}