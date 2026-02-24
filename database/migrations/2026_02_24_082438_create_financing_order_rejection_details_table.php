<?php

declare(strict_types=1);

use App\Enums\FinancingOrderRejectionReason;
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
        Schema::create('financing_order_rejection_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financing_order_id');
            $table->foreign('financing_order_id')->references('id')->on('financing_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('creator_id');
            $table->foreign('creator_id')->references('id')->on('users')->cascadeOnDelete();
            $table->tinyInteger('rejection_reason')->default(FinancingOrderRejectionReason::Rejected);
            $table->string('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financing_order_rejection_details');
    }
};
