<?php

namespace App\Http\Requests\V1\Lender\Orders;

use App\Enums\DatePeriod;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class OrderVolumeRequest extends FormRequest
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
            'period' => ['sometimes', new EnumValue(DatePeriod::class)],
            'starting_date' => ['sometimes', 'before:ending_date', 'date_format:Y-m-d'],
            'ending_date' => ['sometimes', 'date_format:Y-m-d'],
        ];
    }
}
