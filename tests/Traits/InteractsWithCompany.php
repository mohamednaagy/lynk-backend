<?php

namespace Tests\Traits;

use App\Enums\Area;
use App\Enums\CompanyType;
use App\Enums\EdaatInvoiceStatus;
use App\Enums\FinancingOrderStatus;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\EdaatInvoice;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Wallets\Contracts\TransactionServiceInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

trait InteractsWithCompany
{
    /**
     * @param  array  $data
     * @return Company
     */
    public function createCompanyWithoutWallet(
        array $data = []
    ): Company {
        return Company::factory()->create($data);
    }

    /**
     * @param  int  $walletInitialAmount
     * @param  array  $data
     * @return array
     *
     * @throws BindingResolutionException
     */
    private function createCompany(
        int $walletInitialAmount = 2000,
        array $data = []
    ): array {
        $company = $this->createCompanyWithoutWallet($data);

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

    public function createLenderCompany($walletInitialAmount = 2000, $data = [])
    {
        return $this->createCompany($walletInitialAmount, array_merge(['type' => CompanyType::Lender], $data));
    }

    public function createTraderCompany($walletInitialAmount = 2000, $data = [])
    {
        return $this->createCompany($walletInitialAmount, array_merge(['type' => CompanyType::Trader], $data));
    }

    public function createCompanyByArea($area, $walletInitialAmount = 2000, $data = [])
    {
        $areaKey = Area::getKey($area);
        $methodName = 'create'.ucfirst($areaKey).'Company';

        if (! method_exists($this, $methodName)) {
            throw new RuntimeException("Method doesn't exist: $methodName");
        }

        return $this->{$methodName}($walletInitialAmount, $data);
    }

    /**
     * @param  int  $companyId
     * @param  int  $userId
     * @param  array  $data
     * @return FinancingOrder|Model|Builder
     */
    public function createOrder(int $companyId, int $userId, array $data = []): FinancingOrder|Model|Builder
    {
        return FinancingOrder::query()->create(array_merge([
            'company_id' => $companyId,
            'approved_at' => Carbon::now(),
            'creator_id' => $userId,
            'creator_type' => (new User)->getMorphClass(),
            'customer_name' => 'youssof okiel',
            'national_id' => '2553451234',
            'phone_number' => '+966500112233',
            'amount' => 200,
            'selling_price' => 220,
            'status' => FinancingOrderStatus::InProgress,
            'is_verification_required' => true,
        ], $data));
    }

    public function createEdaatInvoice(int $companyId, int $userId, array $data = []): Model|Builder
    {
        return EdaatInvoice::query()->create(array_merge([
            'company_id' => $companyId,
            'creator_id' => $userId,
            'invoice_number' => Str::uuid(),
            'amount' => 1,
            'status' => EdaatInvoiceStatus::Pending,
        ], $data));
    }
}
