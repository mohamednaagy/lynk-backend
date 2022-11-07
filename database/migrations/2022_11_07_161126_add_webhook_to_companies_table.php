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
        Schema::table('companies', function (Blueprint $table) {
            $table->mediumText('webhook_url')->nullable()->after('order_cost');
            $table->text('webhook_secret_key')->nullable()->after('webhook_url');
            $table->tinyInteger('webhook_type')->nullable()->after('webhook_secret_key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['webhook_url', 'webhook_secret_key', 'webhook_type']);
        });
    }
};
