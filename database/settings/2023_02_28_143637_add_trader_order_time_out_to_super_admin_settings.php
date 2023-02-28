<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class AddTraderOrderTimeOutToSuperAdminSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.trader_order_timeout', 5);
    }
}
