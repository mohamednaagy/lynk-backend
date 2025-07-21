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
        Schema::create('company_lender_order_allowed_commodity_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('commodity_type_id');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade')
                ->name('cloa_company_id_foreign');

            $table->foreign('commodity_type_id')
                ->references('id')
                ->on('commodity_types')
                ->onDelete('cascade')
                ->name('cloa_commodity_type_id_foreign');

            $table->unique(['company_id', 'commodity_type_id'], 'cloa_company_commodity_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_lender_order_allowed_commodity_types');
    }
};
