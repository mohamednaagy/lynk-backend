<?php

namespace App\Actions\Admins\Auth;

use App\Actions\Contracts\Admins\Auth\RegisterAdmin;
use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\CreateUser;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class RegisterAdminAction implements RegisterAdmin
{
    /**
     * RegisterLenderAction constructor.
     *
     * @param  CreateUser  $createUser
     * @param  AssignRoleToUser  $assignRoleToUser
     */
    public function __construct(
        protected CreateUser $createUser,
        protected AssignRoleToUser $assignRoleToUser,
    ) {
    }

    /**
     * @param  array  $data
     * @return User
     *
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function handle(array $data): User
    {
        $user = $this->createUser->handle(
            Arr::only($data, [
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'phone_country_code',
                'password',
                'source',
            ])
        );

        $this->assignRoleToUser->handle($user, Role::Admin);

        return $user;
    }
}
