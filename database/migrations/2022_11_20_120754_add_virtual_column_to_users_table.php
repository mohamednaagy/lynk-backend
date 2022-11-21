<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('virtual_company_id_email')
                ->virtualAs('concat_ws(":",company_id,email)')
                ->unique()
                ->after('company_id');

            $table->dropUnique(['email', (new Company())->getForeignKey()]);
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
            $table->dropUnique(['virtual_company_id_email']);
            $table->dropColumn('virtual_company_id_email');
            $table->unique(['email', (new Company())->getForeignKey()]);
        });
    }
};
