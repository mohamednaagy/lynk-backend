<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\GenerateTraderOrderInvoice;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Models\Transaction;
use App\Support\ZatcaEInvoice\InvoiceSpecs;
use App\Support\ZatcaEInvoice\Order;
use App\Support\ZatcaEInvoice\PurchaseLine;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GenerateTraderOrderInvoiceAction implements GenerateTraderOrderInvoice
{
    public function __construct(
        protected GetProjectSettings $getProjectSettings,
        protected GenerateZatcaInvoice $generateZatcaInvoice,
    ) {
    }

    public function handle(TraderOrder $traderOrder, Transaction $creationFeeTransaction)
    {
        $seller = $this->getProjectSettings->handle();
        $financingOrder = $traderOrder->order;
        $financingOrder->load([
            'company' => fn ($query) => $query->withoutGlobalScope(SoftDeletingScope::class),
        ]);
        $company = $financingOrder->company;
        $vatAmount = $company->order_cost->multiply($seller->getVatRate());

        $order = new Order(
            $creationFeeTransaction->reference_number,
            [
                new PurchaseLine(
                    __('zatca/e-invoice.create_order_cost', [
                        'number' => $traderOrder->getKey(),
                    ]),
                    $company->order_cost,
                    $seller->getVatRateInPercentage()
                ),
            ],
            $traderOrder->created_at->clone()->tz('Asia/Riyadh'),
            $financingOrder
        );

        $invoiceSpecs = new InvoiceSpecs(
            $traderOrder,
            $seller,
            $seller->getVatId(),
            $traderOrder->created_at,
            $company->order_cost->add($vatAmount)->formatByDecimal(),
            $vatAmount->formatByDecimal(),
            $order,
            $company->name,
            $creationFeeTransaction
        );

        $this->generateZatcaInvoice->handle($invoiceSpecs, TraderOrderMediaCollection::ZatcaInvoice);
    }
}
