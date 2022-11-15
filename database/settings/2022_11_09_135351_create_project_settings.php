<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('project.project', [
            'company_name' => [
                'ar' => 'LYNK',
                'en' => 'لينك',
            ],
            'company_cr' => '70065554874',
            'vat_id' => '2665656565',
            'vat_rate' => 15,
            'address_line_one' => [
                'ar' => 'شارع الأمير فيصل',
                'en' => 'Prince Faisal Street',
            ],
            'address_line_two' => [
                'ar' => 'الرياض، السعودية',
                'en' => 'Riyadh, Saudi Arabia',
            ],
        ]);
    }
};
