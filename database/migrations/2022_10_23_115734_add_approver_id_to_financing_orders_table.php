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
            $table->unsignedBigInteger('approver_id')->nullable()->after('company_id');
            $table->timestamp('approved_at')->nullable()->after('approver_id');
            $table->foreign('approver_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
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
            $table->dropConstrainedForeignId('approver_id');
            $table->dropColumn(['approved_at']);
        });
    }
};
