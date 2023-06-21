<?php

namespace App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\TraderOrderMode;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTradingRequest extends FormRequest
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
            'reference_number' => ['required_if:mode,'.TraderOrderMode::Manual, 'string', 'max:100'],
            'mode' => ['required', 'string', new EnumValue(TraderOrderMode::class)],
        ];
    }
}
