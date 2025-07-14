<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'notifications_email',
                'company_cr',
                'contract_number',
                'preferred_market_type',
                'does_order_require_approval',
                'notify_admins_about_new_orders',
                'notify_borrowers_about_order_updates',
                'require_initiate_trade_request',
                'force_unique_reference_number',
                'trading_mode',
                'public_status_comment',
                'internal_status_comment',
                'webhook_secret_key',
                'auto_complete_murabaha_order',
                'driver',
                'data',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('data')->nullable();
            $table->string('driver')->after('unique_name')->unique()->nullable();
            $table->string('notifications_email')->nullable();
            $table->string('company_cr')->nullable()->unique();
            $table->string('contract_number')->nullable();
            $table->tinyInteger('preferred_market_type')->unsigned()->nullable();
            $table->boolean('does_order_require_approval')->default(0);
            $table->tinyInteger('notify_admins_about_new_orders')->default(1);
            $table->boolean('notify_borrowers_about_order_updates')->default(0);
            $table->boolean('require_initiate_trade_request')->default(0);
            $table->boolean('force_unique_reference_number')->default(0);
            $table->string('trading_mode')->default('automatic');
            $table->text('public_status_comment')->nullable();
            $table->text('internal_status_comment')->nullable();
            $table->text('webhook_secret_key')->nullable();
            $table->boolean('auto_complete_murabaha_order')->default(0);
        });
    }
};
