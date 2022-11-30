<?php

declare(strict_types=1);

use App\Support\Money\Money;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unique_name')->unique()->nullable();
            $table->string('company_cr')->unique();
            $table->unsignedTinyInteger('status');
            $table->json('data')->nullable();
            $table->decimal('order_cost', 64, 0);
            $table->string('order_cost_currency', 4)->default(Money::getDefaultCurrency());
            $table->boolean('does_order_require_approval')
                ->default(false);
            $table->text('public_status_comment')->nullable();
            $table->text('internal_status_comment')->nullable();
            $table->text('webhook_secret_key')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('companies');
    }
}
