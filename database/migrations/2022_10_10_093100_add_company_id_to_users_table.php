<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(Company::class)
                ->nullable()
                ->after('remember_token')
                ->constrained()
                ->cascadeOnDelete();
                
            $table->dropUnique(['email']);
            $table->unique(['email', (new Company())->getForeignKey()]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignIdFor(Company::class);
            $table->unique('email');
            $table->dropUnique(['email', (new Company())->getForeignKey()]);
        });
    }
};
