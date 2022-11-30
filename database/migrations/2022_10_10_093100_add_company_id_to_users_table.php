<?php

use App\Models\Company;
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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(Company::class)
                ->nullable()
                ->after('remember_token')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('virtual_company_id_email')
                ->virtualAs('concat_ws(":",company_id,email)')
                ->unique()
                ->after('company_id');

            $table->dropUnique(['email']);
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
            $table->dropUnique(['virtual_company_id_email']);
            $table->dropColumn('virtual_company_id_email');
        });
    }
};
