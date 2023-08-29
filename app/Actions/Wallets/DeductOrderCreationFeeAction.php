<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\DeductOrderCreationFee;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Exceptions\NoMatchOrderCostAndValueException;
use App\Models\TieredPricing;
use App\Models\TraderOrder;
use App\Support\ZatcaEInvoice\InvoiceSpecs;
use App\Support\ZatcaEInvoice\Order;
use App\Support\ZatcaEInvoice\PurchaseLine;

class DeductOrderCreationFeeAction implements DeductOrderCreationFee
{
    public function __construct(
        protected CreateTransactions $createTransactions,
        protected CalculateVatAmount $calculateVatAmount,
        protected GetProjectSettings $getProjectSettings,
        protected GenerateZatcaInvoice $generateZatcaInvoice
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

        $totalAmountWithVat = $company->order_cost->add($vatAmount);

        $transaction = $this->createTransactions->handle(
            $wallet,
            TransactionReason::OrderCreationFee,
            $orderCostWithoutVat->add($vatAmount),
            [
                'financing_order_id' => $financingOrder->id,
                'trader_order_id' => $traderOrder->id,
                'reference_number ' => $financingOrder->reference_number,
                'amount' => $financingOrder->amount,
                'order_cost' => $company->order_cost,
                'is_vat_included' => true,
                'vat_percentage' => $vatRate * 100,
                'vat_amount' => $vatAmount,
                'order_cost' => $orderCostWithoutVat,
                'vat_rate' => $vatRate,
                'is_vat_included' => true,
                'pricing_tier' => TieredPricing::getPricingTier($company, $financingOrder->amount),
            ]
        );

        $seller = $this->getProjectSettings->handle();

        $invoiceSpecs = new InvoiceSpecs(
            $transaction,
            $seller,
            $seller->getVatId(),
            $transaction->created_at->clone(),
            $totalAmountWithVat->formatByDecimal(),
            $vatAmount->formatByDecimal(),
            new Order(
                $transaction->reference_number,
                [
                    new PurchaseLine(
                        __('zatca/e-invoice.create_order_cost', [
                            'trader_order_id' => $traderOrder->getKey(),
                            'financing_order_id' => $traderOrder->financing_order_id,
                        ]),
                        $company->order_cost,
                        $vatRate * 100,
                        quantity: 1
                    ),
                ],
                $transaction->created_at->clone()->tz('Asia/Riyadh'),
            ),
            $company->name,
            $transaction,
        );

        $this->generateZatcaInvoice->handle($invoiceSpecs, TransactionMediaCollection::ZatcaInvoice);

        return $transaction;
    }
}
