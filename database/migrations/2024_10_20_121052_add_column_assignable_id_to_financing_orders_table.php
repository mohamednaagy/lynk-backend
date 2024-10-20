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
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('assignable_id')
                ->nullable()
                ->after('is_verification_required');

            $table->foreign('assignable_id')
                ->references('id')->on('users')
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
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->dropForeign(['assignable_id']);
            $table->dropColumn('assignable_id');
        });
    }
};
