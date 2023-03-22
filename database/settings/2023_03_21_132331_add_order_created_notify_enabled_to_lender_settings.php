<?php

use App\Enums\FinancingOrderNotificationSettingStatus;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('area_lender.notify_admins_about_new_orders', FinancingOrderNotificationSettingStatus::BasedOnCompanySettings);
    }
};
