<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class AddWakalaTemplateToSuperAdminSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('area_super_admin.company_wakala_template', '');
        $this->migrator->add('area_super_admin.client_wakala_template', '');
    }
}
