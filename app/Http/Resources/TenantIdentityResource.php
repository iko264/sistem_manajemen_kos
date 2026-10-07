<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantIdentityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = $request->user()?->role === 'admin';

        return [
            'tenancy_id' => $this->id,
            'room_id' => $this->room_id,
            'start_date' => $this->start_date?->toDateString(),
            $this->mergeWhen($isAdmin, [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'identity_number' => $this->user->identity_number,
                'address' => $this->user->address,
            ]),
        ];
    }
}