<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenancyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = $request->user()?->role === 'admin';

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'room_id' => $this->room_id,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'user' => $this->whenLoaded('user', function () use ($isAdmin) {
                $user = [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];

                if ($isAdmin) {
                    $user['phone'] = $this->user->phone;
                    $user['identity_number'] = $this->user->identity_number;
                    $user['address'] = $this->user->address;
                }

                return $user;
            }),
            'room' => $this->whenLoaded('room', fn () => [
                'id' => $this->room->id,
                'number' => $this->room->number,
                'type' => $this->room->type,
                'price' => $this->room->price,
                'status' => $this->room->status,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}