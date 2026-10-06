<?php

namespace App\Http\Requests\Billing;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class GenerateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->allows('generate', Invoice::class);
    }

    public function rules(): array
    {
        return ['period' => ['required', 'date_format:Y-m']];
    }

    public function messages(): array
    {
        return ['period.date_format' => 'Format periode harus YYYY-MM, contoh 2026-10.'];
    }
}
