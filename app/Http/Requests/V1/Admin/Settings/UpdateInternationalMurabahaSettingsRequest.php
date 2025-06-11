<?php

namespace App\Http\Requests\V1\Admin\Settings;

use App\Enums\CommodityTypeStatus;
use App\Enums\Trader;
use App\Models\CommodityType;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $area
 */
class UpdateInternationalMurabahaSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'bursam_default_preferred_commodity_type' => [
                'required',
                'integer',
                'in:'.implode(',', $this->getAllowedCommodityTypes()),
            ],
        ];
    }

    private function getAllowedCommodityTypes(): array
    {
        return CommodityType::where('provider', Trader::Bursam)
            ->where('status', CommodityTypeStatus::Active)
            ->pluck('id')
            ->toArray();
    }
}
