<?php

use App\Models\LocalMarketOrder;
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
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->smallInteger('lender_identifier')->nullable()->after('national_id');
            $table->renameColumn('customer_name', 'borrower_identifier');
        });
        LocalMarketOrder::with('lender')->get()->each(function ($order) {
            $order->lender_identifier = $order->lender?->id;
            $order->save();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->dropColumn('lender_identifier');
            $table->renameColumn('borrower_identifier', 'customer_name');
        });
    }
};
