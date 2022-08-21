<?php

namespace App\Settings\Services;

use Illuminate\Validation\Rule;
use App\Settings\Services\Contracts\SettingsInterface;
use App\Settings\Support\SettingsRegistry;

class GeneralSettingsService implements SettingsInterface
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'default_otp_driver' => ['required', 'string', Rule::in(\Otpify::getOptifyDrivers())]
        ];
    }

    /**
     * @param array $data
     * @return void
     */
    public function update(array $data): void
    {
        $settings = SettingsRegistry::getSettingInstanceByKey($data['area']);

        $settings->default_otp_driver = $data['default_otp_driver'];

        $settings->save();
    }
}
