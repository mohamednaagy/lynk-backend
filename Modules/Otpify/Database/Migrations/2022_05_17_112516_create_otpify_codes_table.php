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
    public function up(): void
    {
        Schema::create('otpify_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('initiator_id');
            $table->string('initiator_type');
            $table->unsignedBigInteger('otpifiable_id')->nullable();
            $table->string('otpifiable_type')->nullable();
            $table->string('otp_code')->nullable();
            $table->timestamp('expiration_date');
            $table->timestamp('expired_at')->nullable();
            $table->json('data');
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
        Schema::dropIfExists('otpify_codes');
    }
};
