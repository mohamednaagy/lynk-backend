<?php

declare(strict_types=1);

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
        Schema::create('trader_order_durations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trader_order_id')
                ->index()
                ->constrained('trader_orders')
                ->cascadeOnDelete();
            $table->unsignedInteger('purchasing_commodity')->nullable();
            $table->unsignedInteger('contract_signed')->nullable();
            $table->unsignedInteger('commodity_sold_to_customer')->nullable();
            $table->unsignedInteger('client_wakala')->nullable();
            $table->unsignedInteger('murabha_offer_issued')->nullable();
            $table->unsignedInteger('customer_delivery_confirmation')->nullable();
            $table->unsignedInteger('murabaha_sale_completed')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trader_order_durations');
    }
};
