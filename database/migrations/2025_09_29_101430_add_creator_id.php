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
        // Drop creator_type from Financing_orders table
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->dropColumn('creator_type');
        });
        // creator creator_id to trader_orders table
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('creator_id')->nullable();
        });
        // rename cancelled_by to creator_id in trader_order_cancel_details table
        Schema::table('trader_order_cancel_details', function (Blueprint $table) {
            $table->renameColumn('cancelled_by', 'creator_id');
        });
        // creator creator_id to trader processed cases  table
        Schema::table('trader_order_proceed_cases', function (Blueprint $table) {
            $table->unsignedBigInteger('creator_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop creator_id from trader_processed_cases table
        Schema::table('trader_order_proceed_cases', function (Blueprint $table) {
            $table->dropColumn('creator_id');
        });
        // Drop creator_id from trader_order_cancel_details table
        Schema::table('trader_order_cancel_details', function (Blueprint $table) {
            $table->renameColumn('creator_id', 'cancelled_by');
        });
        // Drop creator_id from trader_orders table
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->dropColumn('creator_id');
        });
        // Drop creator_id from financing_orders table
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->string('creator_type')->nullable();
        });
    }
};
