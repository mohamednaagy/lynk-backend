<?php

namespace Tests\Traits;

use App\Actions\Contracts\Companies\CreateDefaultPricingTier;
use App\Enums\Area;
use App\Enums\CompanyType;
use App\Enums\EdaatInvoiceStatus;
use App\Enums\FinancingOrderStatus;
use App\Enums\OrderFeeType;
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
    public function createCompanyWithoutWallet(
        array $data = []
    ): Company {
        return Company::factory()->create($data);
    }

    /**
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
            [
                'is_vat_included' => true,
            ]
        );

        app(CreateDefaultPricingTier::class)->handle($company);

        return [
            $company,
            $wallet,
        ];
    }

    public function createLenderCompany($walletInitialAmount = 2000, $data = [])
    {
        return $this->createCompany($walletInitialAmount, array_merge(['type' => CompanyType::Lender], $data));
    }

    public function createLenderCompanyWithStandardOrderCost($walletInitialAmount = 2000, $data = [])
    {
        [$company] = $this->createCompany($walletInitialAmount, array_merge(['type' => CompanyType::Lender], $data));

        return $this->addOrderCostTiersToCompany($company, 1);
    }

    public function createLenderCompanyWithTieredOrderCost($walletInitialAmount = 2000, $data = [], $tiersCount = 3)
    {
        [$company] = $this->createCompany($walletInitialAmount, array_merge(['type' => CompanyType::Lender], $data));

        return $this->addOrderCostTiersToCompany($company, $tiersCount);
    }

    public function addOrderCostTiersToCompany($company, $tiersCount)
    {
        $orderCostTiers = $this->generateOrderCostTiers($tiersCount);
        $company->tieredPricing()->createMany($orderCostTiers);

        return $company;
    }

    private function generateOrderCostTiers(int $tiersCount, $differenceRange = 100000, $initialCostWithVat = 100, $percentageCostDownPerTier = .20, $lastTierType = OrderFeeType::Fixed, $prorationAmount = 500000): array
    {
        $orderCostTiers = [];
        $orderValueStart = 0;

        if ($tiersCount == 1) {
            $orderCostTiers[] = [
                'order_value_start' => 0,
                'order_value_end' => null,
                'fee_type' => 'fixed',
                'order_cost_without_vat' => number_format($initialCostWithVat, 2),
                'proration_amount' => null,
            ];

            return $orderCostTiers;
        }

        for ($i = 1; $i <= $tiersCount; $i++) {
            $orderValueEnd = $orderValueStart + $differenceRange;

            $tier = [
                'order_value_start' => number_format($orderValueStart, 2),
                'order_value_end' => number_format($orderValueEnd, 2),
                'fee_type' => 'fixed',
                'order_cost_without_vat' => number_format($initialCostWithVat, 2),
                'proration_amount' => null,
            ];

            $orderValueStart = $orderValueEnd + 0.01;
            $initialCostWithVat = $initialCostWithVat * (1 - $percentageCostDownPerTier);

            // Check if this is the last tier
            if ($i === $tiersCount) {
                $tier['fee_type'] = $lastTierType;
                $tier['proration_amount'] = $lastTierType == 'proration' ? $prorationAmount : null;
            }
            $orderCostTiers[] = $tier;
        }

        return $orderCostTiers;
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
