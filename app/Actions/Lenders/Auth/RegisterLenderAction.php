<?php

namespace App\Actions\Lenders\Auth;

use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\Companies\CreateCompany;
use App\Actions\Contracts\CreateUser;
use App\Actions\Contracts\Lenders\Auth\RegisterLender;
use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Enums\WalletType;
use App\Models\User;
use Illuminate\Support\Arr;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class RegisterLenderAction implements RegisterLender
{
    /**
     * RegisterLenderAction constructor.
     *
     * @param  CreateUser  $createUser
     * @param  CreateCompany  $createCompany
     * @param  AssignRoleToUser  $assignRoleToUser
     */
    public function __construct(
        protected CreateUser $createUser,
        protected CreateCompany $createCompany,
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
        $company = $this->createCompany->handle([
            'name' => $data['company_name'],
            'unique_name' => $data['company_unique_name'],
            'company_cr' => $data['company_cr'],
            'status' => CompanyStatus::Approved,
            'order_cost' => $data['order_cost'],
        ]);

        tenancy()->initialize($company);

        $company->createWallet([
            'name' => WalletType::CompanyWallet,
            'slug' => WalletType::CompanyWallet,
        ]);

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

        $this->assignRoleToUser->handle($user, Role::LenderAdmin);

        return $user;
    }
}
