<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('project.company_name', [
            'ar' => 'لينك',
            'en' => 'LYNK',
        ]);
        $this->migrator->add('project.address_line_one', [
            'ar' => 'شارع الأمير فيصل',
            'en' => 'Prince Faisal Street',
        ]);
        $this->migrator->add('project.address_line_two', [
            'ar' => 'الرياض، السعودية',
            'en' => 'Riyadh, Saudi Arabia',
        ]);
        $this->migrator->add('project.company_cr', '70065554874');
        $this->migrator->add('project.vat_id', '2665656565');
        $this->migrator->add('project.vat_rate', 0.15);
    }
};
