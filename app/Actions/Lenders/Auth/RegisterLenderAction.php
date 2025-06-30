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
    ) {}

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
        ]);

        $company->lender->lenderDetail()->updateOrCreate(
            ['company_id' => $company->id],
            Arr::only($data, [
                'require_initiate_trade_request',
                'does_order_require_approval',
                'company_cr',
                'notifications_email',
            ])
        );

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
