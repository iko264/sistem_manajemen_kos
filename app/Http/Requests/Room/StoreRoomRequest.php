<?php

namespace App\Http\Requests\Room;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'max:20', 'unique:rooms,number'],
            'type' => ['required', Rule::in(Room::TYPES)],
            'price' => ['required', 'integer', 'min:1', 'max:100000000'],
            // Saat dibuat, kamar hanya boleh 'available' / 'maintenance'.
            // 'occupied' hanya berubah lewat proses check-in (bagian B).
            'status' => ['sometimes', Rule::in([Room::STATUS_AVAILABLE, Room::STATUS_MAINTENANCE])],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
