<?php

namespace App\Http\Requests\V1\Admin\Settings;

use App\Rules\SettingLocaleRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $area
 */
class UpdateCompanySettingsRequest extends FormRequest
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
            'company_name' => ['required', 'array', 'size:2', new SettingLocaleRule()],
            'company_cr' => ['required', 'string', 'max:255'],
            'vat_id' => ['required', 'string', 'max:255'],
            'vat' => ['required', 'numeric', 'between:0,100'],
            'address_line_one' => ['required', 'array', 'size:2', new SettingLocaleRule()],
            'address_line_two' => ['required', 'array', 'size:2', new SettingLocaleRule()],
        ];
    }
}
