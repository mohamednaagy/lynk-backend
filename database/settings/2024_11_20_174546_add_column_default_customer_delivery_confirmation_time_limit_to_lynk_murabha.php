<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('local_murabaha.default_customer_delivery_confirmation_time_limit', 24);
    }
};
