<?php

namespace App\Http\Controllers\Api\V1\Edaat;

use App\Actions\Contracts\Lenders\CalcAmountWithoutVatAndOrdersCount;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Enums\EdaatInvoiceStatus;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Models\EdaatInvoice;
use App\Models\TieredPricing;
use App\Support\Edaat\EdaatService;
use App\Support\ZatcaEInvoice\InvoiceSpecs;
use App\Support\ZatcaEInvoice\Order;
use App\Support\ZatcaEInvoice\PurchaseLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        CalcAmountWithoutVatAndOrdersCount $calcAmountWithoutVatAndOrdersCount
    ) {
        Log::debug('test', [$request->all()]);
        DB::transaction(function () use ($request, $calcAmountWithoutVatAndOrdersCount, $createTransactions, $edaatService) {
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
                    [$amountWithoutVat, $rawOrdersCount] = $calcAmountWithoutVatAndOrdersCount->handle(
                        $company,
                        $amountWithVat
                    );

                    $vatAmount = $amountWithVat->subtract($amountWithoutVat);

                    $transaction = $createTransactions->handle(
                        $wallet,
                        TransactionReason::DepositByEdaat,
                        $amountWithoutVat,
                        [
                            'invoice_number' => $invoice->invoice_number,
                            'is_vat_included' => false,
                        ],
                    );

                    $vatPercentage = $this->getProjectSettings->handle()->getVatRateInPercentage();

                    $vatTransaction = $createTransactions->handle(
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
                        $amountWithVat->formatByDecimal(),
                        $vatAmount,
                        $rawOrdersCount,
                        $vatPercentage
                    );

                    app(GenerateZatcaInvoice::class)->handle($invoiceSpecs, TransactionMediaCollection::ZatcaInvoice);
                }
            }
        });
    }

    private function getInvoiceSpecs(
        $transaction,
        $company,
        $totalAmountWithVat,
        $vatAmount,
        $ordersCount,
        $vatPercentage
    ): InvoiceSpecs {
        $project = $this->getProjectSettings->handle();

        if ($company->isTiered()) {
            $itemCostWithoutVat = $totalAmountWithVat->subtract($vatAmount);
            $ordersCount = 1;
        } else {
            $itemCostWithoutVat = TieredPricing::getOrderCostIfStandard($company)['costWithoutVat'];
        }

        return new InvoiceSpecs(
            $transaction,
            $project,
            $project->getVatId(),
            $transaction->created_at->clone(),
            $totalAmountWithVat,
            $vatAmount,
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
