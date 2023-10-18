<?php

namespace App\Actions\Lenders\Auth;

use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\Companies\CreateCompany;
use App\Actions\Contracts\Companies\CreateDefaultPricingTier;
use App\Actions\Contracts\CreateUser;
use App\Actions\Contracts\Lenders\Auth\RegisterLender;
use App\Enums\Role;
use App\Enums\WalletType;
use App\Models\User;
use Cknow\Money\Money;
use Illuminate\Support\Arr;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class RegisterLenderAction implements RegisterLender
{
    public function __construct(
        protected CreateUser $createUser,
        protected CreateCompany $createCompany,
        protected AssignRoleToUser $assignRoleToUser,
    ) {
    }

    /**
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function handle(array $data): User
    {
        $company = $this->createCompany->handle([
            'name' => $data['company_name'],
            'notifications_email' => $data['notifications_email'],
            'unique_name' => $data['company_unique_name'],
            'company_cr' => $data['company_cr'],
            'status' => $data['company_status'],
            'does_order_require_approval' => $data['does_order_require_approval'],
            'require_initiate_trade_request' => $data['require_initiate_trade_request'],
            'notify_borrowers_about_order_updates' => $data['notify_borrowers_about_order_updates'],
        ]);

        tenancy()->initialize($company);

        $company->createWallet(WalletType::CompanyWallet, Money::getDefaultCurrency());

        app(CreateDefaultPricingTier::class)->handle($company);

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
