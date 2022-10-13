<?php

namespace App\Actions;

use App\Actions\Contracts\CreateCompany;
use App\Actions\Contracts\CreateUser;
use App\Actions\Contracts\LoginUser;
use App\Actions\Contracts\RegisterLender;
use App\Enums\Role;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Grantify\Facades\Grantify;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class RegisterLenderAction implements RegisterLender
{
    /**
     * RegisterLenderAction constructor.
     *
     * @param LoginUser $loginUser
     * @param CreateUser $createUser
     * @param CreateCompany $createCompany
     */
    public function __construct(
        protected LoginUser $loginUser,
        protected CreateUser $createUser,
        protected CreateCompany $createCompany
    ) {}

    /**
     * @param array $data
     * @return array
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function handle(array $data): array
    {
        $user =  DB::transaction(function () use ($data) {
            $company = $this->createCompany->handle([
                'name' => $data['company_name'],
                'unique_name' => $data['company_unique_name'],
                'company_cr' => $data['company_cr'],
            ]);

            tenancy()->initialize($company);

            $user = $this->createUser->handle(
                Arr::only($data,[
                    'first_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'phone_country_code',
                    'password',
                    'source',
                ])
            );

            Grantify::assignRoleToModel($user, Role::LenderAdmin);
            return $user;
        });

        return $this->loginUser->handle($user, $data['source']);
    }
}
