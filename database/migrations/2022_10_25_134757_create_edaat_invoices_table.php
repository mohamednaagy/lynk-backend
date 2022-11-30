<?php

use App\Models\Company;
use App\Models\User;
use App\Support\Money\Money;
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
        if (Schema::hasTable('edaat_invoices')) {
            return;
        }

        Schema::create('edaat_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'creator_id')
                ->constrained('users');
            $table->string('invoice_number')->nullable();
            $table->decimal('amount', 64, 0);
            $table->string('currency', 4)->default(Money::getDefaultCurrency());

            $table->unsignedTinyInteger('status');
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
        Schema::dropIfExists('edaat_invoices');
    }
};
