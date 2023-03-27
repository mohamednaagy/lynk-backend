<?php

use App\Enums\GlobalNewOrderNotificationForAdminStatus;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('area_lender.notify_admins_about_new_orders', GlobalNewOrderNotificationForAdminStatus::BasedOnCompanySettings);
    }
};
