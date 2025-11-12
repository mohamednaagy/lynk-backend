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
        Schema::create('financing_order_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedTinyInteger('status');
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index('status');

            $table->foreign('order_id')
                ->references('id')->on('financing_orders')
                ->onDelete('restrict');

            $table->foreign('creator_id')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financing_order_histories');
    }
};
