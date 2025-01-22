<?php

namespace App\Http\Requests\V1\Admin\Commodities\CommodityItem;

use App\Enums\CommodityTypeStatus;
use Illuminate\Foundation\Http\FormRequest;

class CommodityTypesLiteListRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', 'in:'.CommodityTypeStatus::Active.','.CommodityTypeStatus::Inactive],
        ];
    }
}
