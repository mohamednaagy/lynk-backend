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
        Schema::dropIfExists('local_market_live');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('local_market_live', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('local_market_inventories');
            $table->foreignId('commodity_item_id')->constrained('commodity_items');
            $table->foreignId('commodity_type_id')->constrained('commodity_types');
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('supplier_id')->constrained('companies');
            $table->decimal('price');
            $table->integer('eligible_quantity');
            $table->string('status');
            $table->timestamps();

            $table->index(['eligible_quantity', 'price', 'inventory_id', 'status'], 'search_index');

        });
    }
};
