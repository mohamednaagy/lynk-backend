<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->rename('area_lender.company_registration_status', 'area_lender.default_company_registration_status');
        $this->migrator->rename('area_lender.company_created_by_operation_status', 'area_lender.default_company_status_created_by_operation');
        $this->migrator->rename('area_lender.order_cost', 'area_lender.default_order_cost');
        $this->migrator->add('area_lender.default_does_order_require_approval', true);
    }
};
