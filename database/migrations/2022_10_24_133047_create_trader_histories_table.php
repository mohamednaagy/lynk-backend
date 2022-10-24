<?php

use App\Models\FinancingOrder;
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
    public function up(): void
    {
        Schema::create('trader_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(FinancingOrder::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->string('action');
            $table->json('data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('trader_histories');
    }
};
