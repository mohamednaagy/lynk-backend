<?php

namespace App\Http\Requests\V1\Lender\Orders;

use App\Enums\DatePeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'filter' => ['sometimes', Rule::in(DatePeriod::getValues())],
            'starting_date' => ['sometimes', 'date_format:Y-m-d'],
            'ending_date' => ['sometimes', 'date_format:Y-m-d'],
        ];
    }
}
