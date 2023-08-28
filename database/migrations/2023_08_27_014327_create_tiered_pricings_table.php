<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiered_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->decimal('order_value_start', 64, 0);
            $table->decimal('order_value_end', 64, 0)->nullable();
            $table->string('fee_type', 15);
            $table->decimal('order_cost_without_vat', 64, 0);
            $table->decimal('proration_amount', 64, 0)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiered_pricings');
    }
};
