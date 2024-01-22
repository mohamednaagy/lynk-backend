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
        Schema::table('enquiries', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\Company::class)
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->cascadeOnDelete();
        });

        \App\Models\Enquiry::get()->each(function ($q) {
            $q->update(['company_id' => $q->user->company_id]);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Company::class);
            $table->dropColumn('company_id');

        });
    }
};
