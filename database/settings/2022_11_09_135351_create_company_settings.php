<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('company.company_name', [
            'ar' => fake()->name(),
            'en' => fake()->name(),
        ]);
        $this->migrator->add('company.address_line_one', [
            'ar' => fake()->address(),
            'en' => fake()->address(),
        ]);
        $this->migrator->add('company.address_line_two', [
            'ar' => fake()->address(),
            'en' => fake()->address(),
        ]);
        $this->migrator->add('company.company_cr', fake()->randomNumber());
        $this->migrator->add('company.vat_id', fake()->randomNumber());
        $this->migrator->add('company.vat', fake()->randomFloat(1, 0, 100));
    }
};
