<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'tenant'),
            ],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'start_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Penghuni wajib dipilih.',
            'user_id.exists' => 'Penghuni tidak ditemukan atau bukan berperan tenant.',
            'room_id.required' => 'Kamar wajib dipilih.',
            'room_id.exists' => 'Kamar tidak ditemukan.',
            'start_date.required' => 'Tanggal masuk wajib diisi.',
            'start_date.date' => 'Tanggal masuk harus berupa tanggal yang valid.',
        ];
    }
}