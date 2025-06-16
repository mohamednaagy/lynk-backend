<?php

namespace App\Http\Requests\V1\Supplier\CommodityType;

use App\Enums\CommodityTypeProvider;
use App\Enums\CommodityTypeStatus;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class CommodityTypesLiteListRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', 'in:'.CommodityTypeStatus::Active.','.CommodityTypeStatus::Inactive],
            'provider' => ['nullable', 'string', new EnumValue(CommodityTypeProvider::class)],
        ];
    }
}
