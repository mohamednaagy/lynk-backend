<?php

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
        Schema::create('local_market_unit_ownership', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id');
            $table->string('current_owner')->nullable();
            $table->string('current_owner_type')->comment("Supplier => 1, Company => 2, Customer => ");
            $table->string('status');
            $table->string('previous_owner')->nullable();
            $table->unsignedBigInteger('previous_owner_type')->nullable()->comment("Supplier => 1, Company => 2, Customer => ");
            $table->timestamps();

            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            // Assuming there's a 'users' table for owner_id and previous_owner references
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('previous_owner')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('local_market_unit_ownership');
    }
}