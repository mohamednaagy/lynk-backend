<?php

namespace Tests\Traits;

use App\Enums\CompanyType;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\User;
use App\Support\Wallets\Contracts\TransactionServiceInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Grantify\Facades\Grantify;

trait InteractsWithTrader
{
    /**
     * @param  array  $data
     * @return Company
     */
    public function createTraderWithoutWallet(
        array $data = []
    ): Company {
        return Company::factory()->create(array_merge([
            'first_name' => 'firstName',
            'last_name' => 'lastName',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'test@uselynk.test',
            'password' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => 'companyName',
            'company_unique_name' => 'lynk05',
            'company_cr' => '12345678910',
            'type' => CompanyType::Trader,
        ], $data));
    }

    /**
     * @param  int  $walletInitialAmount
     * @param  array  $data
     * @return array
     *
     * @throws BindingResolutionException
     */
    public function createTrader(
        int $walletInitialAmount = 2000,
        array $data = []
    ): array {
        $company = $this->createTraderWithoutWallet($data);

        $wallet = $company->createWallet(WalletType::CompanyWallet, 'SAR');

        app()->make(TransactionServiceInterface::class)->deposit(
            $wallet,
            \money($walletInitialAmount, 'SAR'),
            1,
            1,
            []
        );

        return [
            $company,
            $wallet,
        ];
    }

    /**
     * @param  int  $companyId
     * @param  string  $role
     * @param  string  $email
     * @param  array  $data
     * @return Collection|Model|mixed
     */
    public function createTraderUser(
        int $companyId,
        string $role,
        string $email = 'trader@bim.com',
        array $data = []
    ): mixed {
        $userLender = User::factory()->create(array_merge([
            'email' => $email,
            'password' => bcrypt('12345678'),
            'company_id' => $companyId,
        ], $data));

        Grantify::assignRoleToModel($userLender, $role);

        return $userLender;
    }
}
