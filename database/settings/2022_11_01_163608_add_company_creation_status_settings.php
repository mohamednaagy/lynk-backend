<?php

use App\Enums\CompanyStatus;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('area_lender.company_registration_status', CompanyStatus::Pending);
        $this->migrator->add('area_lender.company_created_by_operation_status', CompanyStatus::Approved);
    }
};
