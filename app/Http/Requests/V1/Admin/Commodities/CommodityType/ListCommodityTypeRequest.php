<?php

namespace App\Http\Requests\V1\Admin\Commodities\CommodityType;

use App\Enums\CommodityTypeStatus;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }
}
