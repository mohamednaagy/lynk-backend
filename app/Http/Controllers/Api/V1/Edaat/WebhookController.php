<?php

namespace App\Http\Controllers\Api\V1\Edaat;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\Lenders\CalculateAmountWithVat;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\GenerateVoucherReceipt;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Enums\EdaatInvoiceStatus;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Models\EdaatInvoice;
use App\Support\Edaat\EdaatService;
use App\Support\ZatcaEInvoice\InvoiceSpecs;
use App\Support\ZatcaEInvoice\Order;
use App\Support\ZatcaEInvoice\PurchaseLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(protected GetProjectSettings $getProjectSettings)
    {
    }

    public function __invoke(
        Request $request,
        EdaatService $edaatService,
        CreateTransactions $createTransactions,
        CalculateVatAmount $calculateVatAmount,
        CalculateAmountWithVat $calculateAmountWithVat,
        GenerateZatcaInvoice $generateZatcaInvoice
    ) {
        Log::debug('test', [$request->all()]);
        foreach ($request->all() as $invoice) {
            if ($edaatService->isPaidInvoice($invoice['InvoiceNo'])) {
                $invoice = EdaatInvoice::where('id', $invoice['InternalCode'])->lockForUpdate()->first();
                if ($invoice->status->isNot(EdaatInvoiceStatus::Pending)) {
                    continue;
                }
                $company = $invoice->company;
                $wallet = $company->getWallet(WalletType::CompanyWallet);
                $invoice->update(['status' => EdaatInvoiceStatus::Paid]);

                $amountWithVat = $invoice->amount;
                [$vatAmount, $vatRate] = $calculateVatAmount->handle($amountWithVat);
                $amountWithoutVat = $amountWithVat->subtract($vatAmount);

                $transaction = $createTransactions->handle(
                    $wallet,
                    TransactionReason::DepositByEdaat,
                    $amountWithoutVat,
                    [
                        'invoice_number' => $invoice->invoice_number,
                    ]
                );

                app(GenerateVoucherReceipt::class)->handle($transaction);

                $vatTransaction = $createTransactions->handle(
                    $wallet,
                    TransactionReason::VatPercentageOnDeposit,
                    $vatAmount,
                    [
                        'vat_percentage' => $this->getProjectSettings->handle()->getVatRateInPercentage(),
                    ]
                );

                [$chargeAmountWithoutVat, $orderCount] = $calculateAmountWithVat->handle($company, (int) $amountWithVat->formatByDecimal());
                $invoiceSpecs = $this->getInvoiceSpecs(
                    $vatTransaction,
                    $company,
                    (int) $amountWithoutVat->formatByDecimal(),
                    $vatAmount,
                    $orderCount
                );

                $generateZatcaInvoice->handle($invoiceSpecs, TransactionMediaCollection::RechargeReceipt);
            }
        }
    }

    private function getInvoiceSpecs($transaction, $company, $amount, $vatAmount, $orderCount): InvoiceSpecs
    {
        return new InvoiceSpecs(
            $this->getProjectSettings->handle()->getVatId(),
            $transaction,
            $this->getProjectSettings->handle()->getCompanyName(Config::get('app.locale', 'en')),
            $this->getProjectSettings->handle()->getVatId(),
            $transaction->created_at,
            $amount,
            $vatAmount,
            new Order(
                $transaction->reference_number,
                [
                    new PurchaseLine(
                        __('zatca/e-invoice.recharge_balance'),
                        $company->order_cost,
                        $this->getProjectSettings->handle()->getVatRateInPercentage(),
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
