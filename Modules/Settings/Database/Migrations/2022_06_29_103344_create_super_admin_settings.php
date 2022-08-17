<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('area_super_admin.otp_enabled', false);
        $this->migrator->add('area_super_admin.otp_driver', 'email');
    }
};
