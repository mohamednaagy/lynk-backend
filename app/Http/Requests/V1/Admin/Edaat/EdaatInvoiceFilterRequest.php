<?php

namespace App\Http\Requests\V1\Admin\Edaat;

use App\Enums\EdaatInvoiceStatus;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EdaatInvoiceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_number' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', new EnumValue(EdaatInvoiceStatus::class, false)],
            'date_from' => ['sometimes', 'date_format:Y-m-d', Rule::when($this->filled('date_to'), ['before_or_equal:date_to'])],
            'date_to' => ['sometimes', 'date_format:Y-m-d', Rule::when($this->filled('date_from'), ['after_or_equal:date_from'])],
            'amount_gte' => ['sometimes', 'numeric', Rule::when($this->filled('amount_lte'), ['lte:amount_lte'])],
            'amount_lte' => ['sometimes', 'numeric', Rule::when($this->filled('amount_gte'), ['gte:amount_gte'])],
            'oldest' => ['sometimes', 'boolean'],
        ];
    }
}
