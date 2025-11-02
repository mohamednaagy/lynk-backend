<?php

use App\Enums\TraderOrderSettlementStatus;
use App\Models\TraderOrder;
use App\Models\User;
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
        Schema::create('trader_order_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TraderOrder::class)
                ->constrained('trader_orders')
                ->cascadeOnDelete();
            $table->boolean('is_commodities_settled')->nullable();
            $table->tinyInteger('status')
                ->default(TraderOrderSettlementStatus::Pending)
                ->comment('Settlement check status: 0=pending, 1=in_progress, 2=completed, 3=failed');
            $table->foreignIdFor(User::class, 'creator_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trader_order_settlements');
    }
};
