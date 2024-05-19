<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier_locations', function (Blueprint $table) {
            $table->id();
            $table->string('unique_identifier', 255);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->timestamps();

            $table->unique(['company_id', 'unique_identifier']);

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('supplier_locations', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::dropIfExists('supplier_locations');
    }
}
