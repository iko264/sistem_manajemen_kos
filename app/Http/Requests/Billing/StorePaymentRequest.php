<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StorePaymentRequest extends FormRequest
{
    /** Hanya tenant pemilik invoice (InvoicePolicy::pay) -> selain itu 403. */
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->allows('pay', $this->route('invoice'));
    }

    public function rules(): array
    {
        return [
            'proof' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'], // maks 2MB
            'amount' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'proof.max' => 'Ukuran bukti pembayaran maksimal 2MB.',
            'proof.mimes' => 'Bukti pembayaran harus berupa gambar JPG atau PNG.',
        ];
    }
}
