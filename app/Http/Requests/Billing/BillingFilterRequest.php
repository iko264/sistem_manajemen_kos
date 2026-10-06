<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi query string GET /api/billing/tenants dan /api/billing/rooms. */
class BillingFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', Rule::in(['paid', 'unpaid', 'pending', 'overdue'])],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
