<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection;

    public function __construct()
    {
        $this->connection = Config::get('wallet.database.connection');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // this to avoid errors when running "php artisan migrate:fresh". This command will drop all tables
        // in the default connection but not in the "wallet.database.connection"
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('wallets');

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('currency', 4);
            $table->morphs('holder');
            $table->uuid()->unique();
            $table->unique(['holder_id', 'holder_type', 'name'], 'holder_name_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallets');
    }
};
