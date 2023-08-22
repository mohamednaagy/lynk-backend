<?php

namespace App\Http\Requests\V1\Admin\Commodities;

use App\Enums\Trader;
use App\Enums\TraderProductStatus;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ar' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string'],
            'order' => ['required', 'integer'],
            'status' => ['required', 'string', new EnumValue(TraderProductStatus::class)],
            'provider' => ['required', new EnumValue(Trader::class)],
        ];
    }
}
