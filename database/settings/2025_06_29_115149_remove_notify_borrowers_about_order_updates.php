<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->delete('area_lender.notify_borrowers_about_order_updates');
    }

    public function down(): void
    {
        $this->migrator->add('area_lender.notify_borrowers_about_order_updates', false);
    }
};
