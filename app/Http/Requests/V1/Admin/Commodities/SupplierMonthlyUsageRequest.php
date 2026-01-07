<?php

namespace App\Http\Requests\V1\Admin\Commodities;

use Illuminate\Foundation\Http\FormRequest;

class SupplierMonthlyUsageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
