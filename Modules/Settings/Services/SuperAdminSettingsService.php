<?php

namespace Modules\Settings\Services;

use Illuminate\Validation\Rule;
use Modules\Settings\Services\Contracts\SettingsInterface;
use Modules\Settings\Support\SettingsRegistry;

class SuperAdminSettingsService implements SettingsInterface
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'otp_driver' => ['required', 'string', Rule::in(\Otpify::getOptifyDrivers())],
            'otp_enabled' => ['required', 'boolean']
        ];
    }

    /**
     * @param array $data
     * @return void
     */
    public function update(array $data): void
    {
        $settings = SettingsRegistry::getSettingInstanceByKey($data['area']);

        $settings->otp_driver = $data['otp_driver'];
        $settings->otp_enabled = $data['otp_enabled'];

        $settings->save();
    }
}
