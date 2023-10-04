<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\DeductOrderCreationFee;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Exceptions\NoMatchOrderCostAndValueException;
use App\Models\TieredPricing;
use App\Models\TraderOrder;

class DeductOrderCreationFeeAction implements DeductOrderCreationFee
{
    public function __construct(
        protected CreateTransactions $createTransactions,
        protected CalculateVatAmount $calculateVatAmount,
        protected GetProjectSettings $getProjectSettings,
    ) {
    }

    /**
     * @throws NoMatchOrderCostAndValueException
     */
    public function handle(TraderOrder $traderOrder)
    {
        $financingOrder = $traderOrder->order;
        $company = $financingOrder->company()->withTrashed()->first();
        $wallet = $company->getWallet(WalletType::CompanyWallet);

        $orderCostWithoutVat = TieredPricing::getOrderCostWithoutVat($company, $financingOrder->amount);

        [$vatAmount, $vatRate] = $this->calculateVatAmount
            ->setAmount($orderCostWithoutVat)
            ->setIsVatIncludedInAmount(false)
            ->handle();

        $totalAmountWithVat = $orderCostWithoutVat->add($vatAmount);

        return $this->createTransactions->handle(
            $wallet,
            TransactionReason::OrderCreationFee,
            $totalAmountWithVat,
            [
                'financing_order_id' => $financingOrder->id,
                'trader_order_id' => $traderOrder->id,
                'reference_number ' => $financingOrder->reference_number,
                'amount' => $financingOrder->amount,
                'vat_percentage' => $vatRate * 100,
                'vat_amount' => $vatAmount,
                'order_cost' => $orderCostWithoutVat,
                'vat_rate' => $vatRate,
                'is_vat_included' => true,
                'pricing_tier' => TieredPricing::getPricingTier($company, $financingOrder->amount),
            ]
        );
    }
}
