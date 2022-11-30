<?php

namespace App\Http\Requests\V1\Lender\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $area
 */
class UpdateSettingsRequest extends FormRequest
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
            'does_order_require_approval' => ['required', 'boolean'],
        ];
    }
}
