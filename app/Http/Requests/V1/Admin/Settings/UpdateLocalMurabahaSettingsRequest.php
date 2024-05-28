<?php

namespace App\Http\Requests\V1\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $area
 */
class UpdateLocalMurabahaSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'default_trade_order_roatation_count' => ['required', 'in:0,1,2,3'],
        ];
    }
}
