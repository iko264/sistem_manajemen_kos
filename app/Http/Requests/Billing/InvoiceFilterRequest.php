<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi query string GET /api/invoices. */
class InvoiceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['unpaid', 'pending', 'paid'])],
            'period' => ['nullable', 'date_format:Y-m'],
            'overdue' => ['nullable', 'boolean'],
            'room_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
