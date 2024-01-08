<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\Lenders\CalcAmountWithoutVatAndOrdersCount;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\CheckEdaatInvoiceStatus;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Enums\EdaatInvoiceStatus;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\EdaatInvoice;
use App\Models\TieredPricing;
use App\Support\Edaat\EdaatService;
use App\Support\ZatcaEInvoice\InvoiceSpecs;
use App\Support\ZatcaEInvoice\Order;
use App\Support\ZatcaEInvoice\PurchaseLine;

class CheckEdaatInvoiceStatusAction implements CheckEdaatInvoiceStatus
{
    public function __construct(
        protected EdaatService $edaatService,
        protected CreateTransactions $createTransactions,
        protected CalcAmountWithoutVatAndOrdersCount $calcAmountWithoutVatAndOrdersCount,
        protected GetProjectSettings $getProjectSettings,
        protected CalculateVatAmount $calculateVatAmount
    ) {
    }

    public function handle(EdaatInvoice $edaatInvoice): void
    {
        if ($edaatInvoice->status == EdaatInvoiceStatus::Pending()) {
            if ($this->edaatService->isPaidInvoice($edaatInvoice->invoice_number)) {
                $edaatInvoice->update(['status' => EdaatInvoiceStatus::Paid]);

                $company = $edaatInvoice->company;
                $wallet = $company->getWallet(WalletType::CompanyWallet);
                $totalAmountWithVat = $edaatInvoice->amount;

                [$vatAmount, $vatRate] = $this->calculateVatAmount
                    ->setAmount($totalAmountWithVat)
                    ->setIsVatIncludedInAmount(true)
                    ->handle();

                $transaction = $this->createTransactions->handle(
                    $wallet,
                    TransactionReason::DepositByEdaat,
                    $totalAmountWithVat->subtract($vatAmount),
                    [
                        'invoice_number' => $edaatInvoice->invoice_number,
                        'is_vat_included' => false,
                    ],
                );

                $vatPercentage = $vatRate * 100;
                $vatTransaction = $this->createTransactions->handle(
                    $wallet,
                    TransactionReason::VatPercentageOnDeposit,
                    $vatAmount,
                    [
                        'vat_percentage' => $vatPercentage,
                    ],
                    referenceNumber: $transaction->reference_number
                );

                $invoiceSpecs = $this->getInvoiceSpecs(
                    $vatTransaction,
                    $company,
                    $totalAmountWithVat,
                    $vatAmount,
                    $vatPercentage
                );

                app(GenerateZatcaInvoice::class)->handle($invoiceSpecs, TransactionMediaCollection::ZatcaInvoice);
            }
        }
    }

    private function getInvoiceSpecs(
        $transaction,
        $company,
        $totalAmountWithVat,
        $vatAmount,
        $vatPercentage
    ): InvoiceSpecs {
        $project = $this->getProjectSettings->handle();

        if ($company->isTiered()) {
            $itemCostWithoutVat = $totalAmountWithVat->subtract($vatAmount);
            $ordersCount = 1;
        } else {
            $itemCostWithoutVat = TieredPricing::getOrderCostIfStandard($company)['costWithoutVat'];
            [ , , ,$ordersCount] = $this->calcAmountWithoutVatAndOrdersCount
                ->handle($company, $totalAmountWithVat);
        }

        return new InvoiceSpecs(
            $transaction,
            $project,
            $project->getVatId(),
            $transaction->created_at->clone(),
            $totalAmountWithVat->convertAndFormatByDecimal(),
            $vatAmount->convertAndFormatByDecimal(),
            new Order(
                $transaction->reference_number,
                [
                    new PurchaseLine(
                        __('zatca/e-invoice.recharge_balance'),
                        $itemCostWithoutVat,
                        $vatPercentage,
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
