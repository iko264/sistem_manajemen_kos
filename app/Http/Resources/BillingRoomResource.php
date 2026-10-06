<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Membungkus Room dengan kolom agregat hasil withCount/withSum. */
class BillingRoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'room_id' => $this->id,
            'number' => $this->number,
            'type' => $this->type,
            'status' => $this->status,
            'total_invoices' => (int) $this->total_invoices,
            'total_amount' => (int) $this->total_amount,
            'total_paid' => (int) $this->total_paid,
            'total_unpaid' => (int) $this->total_unpaid, // unpaid + pending (belum lunas)
            'overdue_count' => (int) $this->overdue_count,
        ];
    }
}
