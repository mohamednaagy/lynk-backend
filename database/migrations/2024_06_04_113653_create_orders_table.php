<?php

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
        Schema::create('local_market_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->json('preferred_commodity_type')->nullable();
            $table->decimal('amount', 64, 0);
            $table->string('currency', 4);
            $table->unsignedTinyInteger('status')->default(\App\Enums\LocalMarketOrderStatus::initiate);
            $table->text('data')->nullable();
            $table->text('comment')->nullable();
            $table->string('source');
            $table->string('external_order_no', 255);
            $table->string('national_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('buying_uuid')->nullable();
            $table->string('selling_uuid')->nullable();
            $table->string('order_no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('local_market_orders');
    }
};
