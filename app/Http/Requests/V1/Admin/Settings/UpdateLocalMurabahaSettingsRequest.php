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
            'default_contract_sign_time_limit' => ['required', 'integer', 'gt:0'],
        ];
    }
}
