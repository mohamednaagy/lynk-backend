<?php

namespace Modules\Admin\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Permission\Enums\Area;
use Modules\Settings\Support\SettingsRegistry;

class UpdateSettingsRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        $settingService = SettingsRegistry::getSettingServiceByKey($this->area);

        return array_merge([
            'area' => ['required', Rule::in(Area::getValues())]
        ], $settingService->rules());
    }

}
