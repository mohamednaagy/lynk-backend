<?php

namespace App\Http\Requests\Settings;

use Illuminate\Validation\Rule;
use App\Enums\Area;
use Illuminate\Foundation\Http\FormRequest;
use App\Settings\Support\SettingsRegistry;

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
        $settingService = SettingsRegistry::getSettingServiceByKey($this->area);

        return array_merge([
            'area' => ['required', Rule::in(Area::getValues())]
        ], $settingService->rules());
    }

}
