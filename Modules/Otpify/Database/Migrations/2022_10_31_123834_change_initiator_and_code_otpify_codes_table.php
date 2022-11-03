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
        Schema::table('otpify_codes', function (Blueprint $table) {
            $table->unsignedBigInteger('initiator_id')->nullable()->change();
            $table->string('initiator_type')->nullable()->change();
            $table->string('otp_code')->nullable()->change();
        }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
