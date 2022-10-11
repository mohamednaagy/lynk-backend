<?php

use App\Models\Company;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedTinyInteger('status')->default(0);
            $table->bigInteger('reference_number')->unique()->nullable();
            $table->bigInteger('national_id');
            $table->double('amount');
            $table->double('selling_price');

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
        Schema::dropIfExists('financing_orders');
    }
};
