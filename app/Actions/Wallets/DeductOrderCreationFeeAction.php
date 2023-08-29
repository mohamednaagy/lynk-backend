<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Companies\CalculateVatAmount;
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
        protected CalculateVatAmount $calculateVatAmount
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

        $orderCostWithoutVat = TieredPricing::getOrderCost($company, $financingOrder->amount);

        [$vatAmount] = $this->calculateVatAmount
            ->setAmount($orderCostWithoutVat)
            ->setIsVatIncludedInAmount(false)
            ->handle();

        return $this->createTransactions->handle(
            $wallet,
            TransactionReason::OrderCreationFee,
            $orderCostWithoutVat->add($vatAmount),
            [
                'financing_order_id' => $financingOrder->id,
                'trader_order_id' => $traderOrder->id,
                'reference_number ' => $financingOrder->reference_number,
                'amount' => $financingOrder->amount,
                'order_cost' => $orderCostWithoutVat,
                'is_vat_included' => true,
                'pricing_tier' => TieredPricing::getPricingTier($company, $financingOrder->amount),
            ]
        );
    }
}
