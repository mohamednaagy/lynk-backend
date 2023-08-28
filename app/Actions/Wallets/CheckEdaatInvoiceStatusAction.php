<?php

namespace App\Actions\Wallets;

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
        protected GetProjectSettings $getProjectSettings
    ) {
    }

    public function handle(EdaatInvoice $edaatInvoice): void
    {
        if ($edaatInvoice->status == EdaatInvoiceStatus::Pending()) {
            if ($this->edaatService->isPaidInvoice($edaatInvoice->invoice_number)) {
                $edaatInvoice->update(['status' => EdaatInvoiceStatus::Paid]);

                $wallet = $edaatInvoice->company->getWallet(WalletType::CompanyWallet);

                $amountWithVat = $edaatInvoice->amount;
                [$amountWithoutVat,, $vatRate, $rawOrdersCount] = $this->calcAmountWithoutVatAndOrdersCount->handle(
                    $edaatInvoice->company,
                    $amountWithVat
                );

                $vatAmount = $amountWithVat->subtract($amountWithoutVat);

                $transaction = $this->createTransactions->handle(
                    $wallet,
                    TransactionReason::DepositByEdaat,
                    $amountWithoutVat,
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
                    $edaatInvoice->company,
                    $amountWithVat->formatByDecimal(),
                    $vatAmount,
                    $rawOrdersCount,
                    $vatPercentage
                );

                app(GenerateZatcaInvoice::class)->handle($invoiceSpecs, TransactionMediaCollection::ZatcaInvoice);
            }
        }
    }

    private function getInvoiceSpecs(
        $transaction,
        $company,
        $amountWithVat,
        $vatAmount,
        $orderCount,
        $vatPercentage
    ): InvoiceSpecs {
        $project = $this->getProjectSettings->handle();

        return new InvoiceSpecs(
            $transaction,
            $project,
            $project->getVatId(),
            $transaction->created_at->clone(),
            $amountWithVat,
            $vatAmount,
            new Order(
                $transaction->reference_number,
                [
                    new PurchaseLine(
                        __('zatca/e-invoice.recharge_balance'),
                        $company->order_cost,
                        $vatPercentage,
                        quantity: $orderCount
                    ),
                ],
                $transaction->created_at->clone()->tz('Asia/Riyadh'),
            ),
            $company->name,
            $transaction,
        );
    }
}
