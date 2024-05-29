<?php

namespace App\Http\Requests\V1\Supplier\CommodityType;

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
            'status' => ['required',  new EnumValue(CommodityTypeStatus::class)],
        ];
    }

    public function prepareForValidation()
    {
        $status = $this->input('status');
        if (is_null($status)) {
            $this->merge(['status' => CommodityTypeStatus::Active]);
        }
    }
}
