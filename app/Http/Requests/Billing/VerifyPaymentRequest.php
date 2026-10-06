<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VerifyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->allows('verify', $this->route('payment'));
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['required_if:action,reject', 'nullable', 'string', 'max:500'], // wajib saat reject
        ];
    }

    public function messages(): array
    {
        return ['note.required_if' => 'Catatan wajib diisi saat menolak pembayaran.'];
    }
}
