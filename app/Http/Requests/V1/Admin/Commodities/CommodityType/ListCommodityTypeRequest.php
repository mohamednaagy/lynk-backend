<?php

namespace App\Http\Requests\V1\Admin\Commodities\CommodityType;

use App\Enums\CommodityTypeStatus;
use App\Enums\Trader;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCommodityTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'status' => ['nullable',  new EnumValue(CommodityTypeStatus::class)],
            'active' => ['nullable', 'integer', Rule::in([1, 2, 3])],
            'provider' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value === null) {
                    return;
                }

                $providers = explode(',', $value);
                foreach ($providers as $provider) {
                    $provider = trim($provider);
                    if (! Trader::hasValue($provider)) {
                        $fail("The {$attribute} contains invalid provider value: {$provider}.");
                    }
                }
            }],
            'name' => ['nullable', 'string', 'max:255'],
            'unique_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
