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
            'does_order_require_approval' => ['required', 'boolean'],
            'require_initiate_trade_request' => ['required', 'boolean'],
            'notify_borrowers_about_order_updates' => ['required', 'boolean'],
            'force_unique_reference_number' => ['required', 'boolean'],
            'token_expire_in' => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'token_expire_in.gt' => __('validation.greater_than_zero'),
        ];
    }
}
