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
        // Add optimized index for frequent query on trader_histories
        Schema::table('trader_histories', function (Blueprint $table) {
            $table->index(['trader_order_id', 'id'], 'trader_histories_order_id_latest_idx');
        });

        // Add caching columns to trader_orders
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->unsignedInteger('last_history_action')->nullable()->comment('Cached last history action for performance optimization');
            $table->timestamp('last_history_action_updated_at')->nullable()->comment('When the cached last history action was updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove caching columns from trader_orders
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->dropColumn(['last_history_action', 'last_history_action_updated_at']);
        });

        // Remove optimized index from trader_histories
        Schema::table('trader_histories', function (Blueprint $table) {
            $table->dropIndex('trader_histories_order_id_latest_idx');
        });
    }
};
