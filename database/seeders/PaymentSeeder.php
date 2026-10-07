<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/** Payment konsisten dengan status invoice: paid->approved, pending->pending, unpaid lewat tempo->rejected. */
class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        // PNG 1x1 sebagai bukti contoh agar proof_url bisa dibuka.
        $proof = 'payment-proofs/seed-proof.png';
        Storage::disk('public')->put($proof, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        ));

        $admin = User::where('email', 'admin@kos.test')->first();

        foreach (Invoice::doesntHave('payments')->orderBy('id')->get() as $invoice) {
            $base = ['invoice_id' => $invoice->id, 'amount' => $invoice->amount, 'proof_path' => $proof];

            if ($invoice->status === 'paid') {
                $when = $invoice->due_date->copy()->subDays(3);
                Payment::create($base + [
                    'status' => 'approved',
                    'verified_by' => $admin?->id,
                    'verified_at' => $when->isFuture() ? now() : $when,
                    'note' => 'Pembayaran sesuai.',
                ]);
            } elseif ($invoice->status === 'pending') {
                Payment::create($base + ['status' => 'pending']);
            } elseif ($invoice->is_overdue) {
                Payment::create($base + [
                    'status' => 'rejected',
                    'verified_by' => $admin?->id,
                    'verified_at' => now()->subDay(),
                    'note' => 'Bukti transfer tidak terbaca, mohon unggah ulang.',
                ]);
            }
        }
    }
}
