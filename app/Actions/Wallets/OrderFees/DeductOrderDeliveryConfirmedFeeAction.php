<?php

namespace App\Actions\Wallets\OrderFees;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\OrderFees\DeductOrderDeliveryConfirmedFee;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Exceptions\NoMatchOrderCostAndValueException;
use App\Models\TieredPricing;
use App\Models\TraderOrder;

class DeductOrderDeliveryConfirmedFeeAction implements DeductOrderDeliveryConfirmedFee
{
    public function __construct(
        protected CreateTransactions $createTransactions,
        protected CalculateVatAmount $calculateVatAmount,
        protected GetProjectSettings $getProjectSettings,
    ) {}

    /**
     * @throws NoMatchOrderCostAndValueException
     */
    public function handle(TraderOrder $traderOrder)
    {
        $financingOrder = $traderOrder->order;
        $lender = $financingOrder->lender()->withTrashed()->first();
        $wallet = $lender->getWallet(WalletType::CompanyWallet);

        $orderCostWithoutVat = TieredPricing::getOrderCostWithoutVat($lender, $financingOrder->amount);

        $vatRate = $this->getProjectSettings->handle()->getVatRate();
        $vatAmount = TieredPricing::getVatAmount($lender, $financingOrder->amount, $orderCostWithoutVat);

        $totalAmountWithVat = $orderCostWithoutVat->add($vatAmount);

        return $this->createTransactions->handle(
            $wallet,
            TransactionReason::DeliveryConfirmedFee,
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
                'pricing_tier' => TieredPricing::getPricingTier($lender, $financingOrder->amount),
            ]
        );
    }
}
