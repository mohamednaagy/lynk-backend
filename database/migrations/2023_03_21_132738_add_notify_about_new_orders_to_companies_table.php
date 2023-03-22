<?php

use App\Enums\FinancingOrderNotificationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table
                ->tinyInteger('notify_admins_about_new_orders')
                ->default(FinancingOrderNotificationStatus::On)
                ->after('does_order_require_approval');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('notify_admins_about_new_orders');
        });
    }
};
