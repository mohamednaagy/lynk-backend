<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->migrator->rename('local_murabaha.default_trade_order_roatation_count', 'local_murabaha.default_trade_order_rotation_count');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->migrator->rename('local_murabaha.default_trade_order_rotation_count', 'local_murabaha.default_trade_order_roatation_count');

    }
};
