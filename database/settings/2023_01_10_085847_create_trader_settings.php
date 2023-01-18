<?php

use App\Enums\CompanyStatus;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('area_trader.default_company_status_created_by_operation', CompanyStatus::Approved);
        $this->migrator->add('area_trader.default_order_cost', '0');
    }
};
