<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Membungkus Tenancy aktif (dengan user, room, dan invoice periode terpilih). */
class BillingTenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $invoice = $this->relationLoaded('invoices') ? $this->invoices->first() : null;

        return [
            'tenancy_id' => $this->id,
            'user' => ['id' => $this->user->id, 'name' => $this->user->name, 'email' => $this->user->email],
            'room' => ['id' => $this->room->id, 'number' => $this->room->number, 'type' => $this->room->type],
            'period' => $request->query('period', now()->format('Y-m')),
            'payment_status' => $invoice ? $invoice->status : 'no_invoice', // paid|unpaid|pending|no_invoice
            'is_overdue' => $invoice ? $invoice->is_overdue : false,
            'invoice' => $invoice ? [
                'id' => $invoice->id,
                'amount' => $invoice->amount,
                'due_date' => $invoice->due_date?->toDateString(),
                'status' => $invoice->status,
                'is_overdue' => $invoice->is_overdue,
            ] : null,
        ];
    }
}
