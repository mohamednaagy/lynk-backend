<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('company_lender_details', function (Blueprint $table) {
            $table->dropColumn('notify_admins_about_new_orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_lender_details', function (Blueprint $table) {
            $table->integer('notify_admins_about_new_orders')->default(1); // Default to On
        });
    }
};
