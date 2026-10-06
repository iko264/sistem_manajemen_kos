<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenancy_id' => $this->tenancy_id,
            'period' => $this->period,
            'amount' => $this->amount,
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'is_overdue' => $this->is_overdue, // dihitung, tidak ada kolom di DB
            'tenancy' => $this->whenLoaded('tenancy', fn () => [
                'id' => $this->tenancy->id,
                'user' => $this->tenancy->relationLoaded('user') && $this->tenancy->user ? [
                    'id' => $this->tenancy->user->id,
                    'name' => $this->tenancy->user->name,
                    'email' => $this->tenancy->user->email,
                ] : null,
                'room' => $this->tenancy->relationLoaded('room') && $this->tenancy->room ? [
                    'id' => $this->tenancy->room->id,
                    'number' => $this->tenancy->room->number,
                    'type' => $this->tenancy->room->type,
                ] : null,
            ]),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
