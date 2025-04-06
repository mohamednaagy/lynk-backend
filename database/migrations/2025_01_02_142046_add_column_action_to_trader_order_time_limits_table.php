<?php

use App\Enums\TraderOrderTimeLimitAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add the `action` column to the `trader_order_time_limits` table.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('trader_order_time_limits')) {
            Schema::table('trader_order_time_limits', function (Blueprint $table) {
                if (! Schema::hasColumn('trader_order_time_limits', 'action')) {
                    $table->tinyInteger('action')
                        ->default(TraderOrderTimeLimitAction::AutoCancelOrder)
                        ->comment('Defines the action to be taken when the time limit is reached');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Remove the `action` column from the `trader_order_time_limits` table.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('trader_order_time_limits')) {
            Schema::table('trader_order_time_limits', function (Blueprint $table) {
                if (Schema::hasColumn('trader_order_time_limits', 'action')) {
                    $table->dropColumn('action');
                }
            });
        }
    }
};
