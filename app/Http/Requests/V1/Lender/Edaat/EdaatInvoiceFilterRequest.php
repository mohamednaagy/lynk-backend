<?php

namespace App\Http\Requests\V1\Lender\Edaat;

use App\Enums\EdaatInvoiceStatus;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

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
            'status' => ['sometimes', 'int', new EnumValue(EdaatInvoiceStatus::class)],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'date_format:Y-m-d'],
            'amount_gte' => ['sometimes', 'numeric'],
            'amount_lte' => ['sometimes', 'numeric'],
            'oldest' => ['sometimes', 'boolean'],
        ];
    }
}
