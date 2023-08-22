<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\Companies\ChargeLenderBalanceManually;
use App\Actions\Contracts\Lenders\CalcAmountWithoutVatAndOrdersCount;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\GenerateVoucherReceipt;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\Transaction;
use App\Support\ZatcaEInvoice\InvoiceSpecs;
use App\Support\ZatcaEInvoice\Order;
use App\Support\ZatcaEInvoice\PurchaseLine;
use Cknow\Money\Money;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class ChargeLenderBalanceManuallyAction implements ChargeLenderBalanceManually
{
    public function __construct(
        protected CreateTransactions $createTransactions,
        protected GenerateVoucherReceipt $generateVoucherReceipt,
        protected GenerateZatcaInvoice $generateZatcaInvoice,
        protected CalculateVatAmount $calculateVatAmount,
        protected GetProjectSettings $getProjectSettings,
        protected CalcAmountWithoutVatAndOrdersCount $calcAmountWithoutVatAndOrdersCount
    ) {
    }

    public function handle(Company $company, array $data)
    {
        $wallet = $company->getWallet(WalletType::CompanyWallet);
        $totalAmountWithVat = Money::parseByDecimal(Arr::get($data, 'amount'), $wallet->currency);
        [$vatAmount, $vatRate] = $this->calculateVatAmount
            ->setAmount($totalAmountWithVat)
            ->setIsVatIncludedInAmount(true)
            ->handle();

        $transaction = $this->createTransactions->handle(
            $wallet,
            TransactionReason::ManualDeposit,
            $totalAmountWithVat->subtract($vatAmount),
            Arr::only($data, ['description_en', 'description_ar'])
        );

        $transaction->addMedia(Arr::get($data, 'attachment'))
            ->toMediaCollection(TransactionMediaCollection::Attachments);

        $this->generateVoucherReceipt->handle($transaction);

        $vatTransaction = $this->createTransactions->handle(
            $wallet,
            TransactionReason::VatPercentageOnDeposit,
            $vatAmount,
            [
                'vat_percentage' => $vatRate * 100,
            ],
            referenceNumber: $transaction->reference_number
        );

        $invoiceSpecs = $this->getInvoiceSpecs(
            $vatTransaction,
            $company,
            $totalAmountWithVat,
            $vatAmount,
            $vatRate
        );

        $this->generateZatcaInvoice->handle($invoiceSpecs, TransactionMediaCollection::RechargeReceipt);

        return $transaction;
    }

    private function getInvoiceSpecs(
        Transaction $transaction,
        Company $company,
        Money $totalAmountWithVat,
        $vatAmount,
        $vatRate
    ): InvoiceSpecs {
        [, $ordersCount] = $this->calcAmountWithoutVatAndOrdersCount
            ->handle($company, $totalAmountWithVat);

        return new InvoiceSpecs(
            $transaction,
            $this->getProjectSettings->handle()->getCompanyName(Config::get('app.locale', 'en')),
            $this->getProjectSettings->handle()->getVatId(),
            $transaction->created_at->clone(),
            $totalAmountWithVat->formatByDecimal(),
            $vatAmount->formatByDecimal(),
            new Order(
                $transaction->reference_number,
                [
                    new PurchaseLine(
                        __('zatca/e-invoice.recharge_balance'),
                        $company->order_cost,
                        $vatRate * 100,
                        quantity: $ordersCount
                    ),
                ],
                $transaction->created_at->clone()->tz('Asia/Riyadh'),
            ),
            $company->name,
            $transaction,
        );
    }
}
