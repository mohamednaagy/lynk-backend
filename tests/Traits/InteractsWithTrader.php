<?php

namespace Tests\Traits;

use App\Enums\Role;
use App\Models\User;
use Modules\Grantify\Facades\Grantify;

trait InteractsWithTrader
{
    /**
     * Summary of createTraderAdmin
     *
     * @param  string  $email
     * @param  array  $data
     * @return mixed
     */
    public function createTraderAdmin(
        string $email = 'traderAdmin@bim.com',
        array $data = []
    ): mixed {
        $admin = User::factory()->create(
            array_merge([
                'email' => $email,
                'password' => bcrypt('12345678'),
            ], $data)
        );

        Grantify::assignRoleToModel($admin, Role::TraderAdmin);

        return $admin;
    }
}
