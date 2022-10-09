<?php

use App\Actions\Contracts\CreateAdminWithRoleAndPermission;
use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $createAdminAction = app(CreateAdminWithRoleAndPermission::class);

        $createAdminAction->handle([
            'first_name' => 'First',
            'last_name' => 'Admin',
            'password' => '12345678',
            'phone_number' => '0503111243',
            'phone_country_code' => 'SA',
            'email' => 'admin@bimventures.com',
            'role' => Role::Admin,
            'permissions' => [],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
