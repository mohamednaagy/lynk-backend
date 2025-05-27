<?php

namespace App\Http\Requests\V1\Admin\Settings;

use App\Enums\TraderProductStatus;
use App\Models\TraderProduct;
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
        return TraderProduct::where('provider', 'Bursam')
            ->where('status', TraderProductStatus::Enabled)
            ->pluck('id')
            ->toArray();
    }
}
