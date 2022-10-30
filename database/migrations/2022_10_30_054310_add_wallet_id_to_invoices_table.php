<?php

use Bavix\Wallet\Models\Wallet;
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
        Schema::table('edaat_invoices', function (Blueprint $table) {
            $table->foreignIdFor(Wallet::class)
                ->after('company_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('edaat_invoices', function (Blueprint $table) {
            $table->dropForeignIdFor(Wallet::class);
        });
    }
};
