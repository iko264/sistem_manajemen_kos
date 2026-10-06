<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Tenancy;
use Illuminate\Database\Seeder;

/**
 * Invoice per bulan dari start_date sampai bulan ini untuk tiap tenancy aktif (B).
 * Pola status per tenancy (urut id): [paid, unpaid(lewat jatuh tempo), pending] / [paid, unpaid] / [unpaid].
 */
class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            0 => ['paid', 'unpaid', 'pending'],
            1 => ['paid', 'unpaid'],
            2 => ['unpaid'],
        ];

        $tenancies = Tenancy::with('room')->where('status', 'active')->orderBy('id')->get();

        foreach ($tenancies as $i => $tenancy) {
            $month = $tenancy->start_date->copy()->startOfMonth();
            $last = now()->startOfMonth();
            $n = 0;

            while ($month->lte($last)) {
                Invoice::firstOrCreate(
                    ['tenancy_id' => $tenancy->id, 'period' => $month->format('Y-m')],
                    [
                        'amount' => $tenancy->room->price,
                        'due_date' => $month->copy()->day(10)->toDateString(),
                        'status' => ($plans[$i] ?? [])[$n] ?? 'unpaid',
                    ]
                );
                $month = $month->copy()->addMonth();
                $n++;
            }
        }
    }
}
