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
        Schema::create('local_market_eligible_quantities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')
                ->constrained('local_market_inventories')
                ->onDelete('cascade');
            $table->foreignId('company_id')
                ->constrained('companies')
                ->onDelete('cascade');
            $table->integer('eligible_quantity');
            $table->timestamps();

            $table->unique(['inventory_id', 'company_id'], 'inventory_company_unique');
            $table->index(['company_id', 'inventory_id', 'eligible_quantity'], 'company_quantity_idx');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('local_market_eligible_quantities');
    }
};
