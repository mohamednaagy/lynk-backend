<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Grantify\Facades\GrantifySeeder;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        GrantifySeeder::seedRoles();
        GrantifySeeder::seedPermissions(true);
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
