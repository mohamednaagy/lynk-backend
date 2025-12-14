<?php

namespace App\Http\Requests\V1\Admin\Companies;

use App\Enums\CompanyMarketType;
use App\Enums\TraderOrderMode;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class CompaniesListFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'trading_mode' => ['nullable', new EnumValue(TraderOrderMode::class, false)],
            'market_type' => ['nullable', 'array'],
            'market_type.*' => [new EnumValue(CompanyMarketType::class, false)],
        ];
    }
}
