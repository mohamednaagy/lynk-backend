<?php

use App\Models\Company;
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
        Schema::create('financing_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->bigInteger('reference_number')->nullable();
            $table->bigInteger('national_id');
            $table->double('amount');
            $table->double('selling_price');
            $table->unsignedTinyInteger('status');

            $table->timestamps();
        });

        Schema::table('financing_orders', function (Blueprint $table) {
            $table->unique(['reference_number', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financing_orders');
    }
};
