<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('area_customer.otp_enabled', true);
        $this->migrator->add('area_customer.otp_driver', 'twilio');
    }
};
