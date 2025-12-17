<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\GenerateTraderOrderInvoice;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
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
    ) {}

    public function handle(TraderOrder $traderOrder, Transaction $creationFeeTransaction)
    {
        $seller = $this->getProjectSettings->handle();
        $financingOrder = $traderOrder->order;
        $financingOrder->load([
            'lender' => fn ($query) => $query->withoutGlobalScope(SoftDeletingScope::class),
        ]);
        $lender = $financingOrder->lender;
        $vatAmount = $lender->order_cost->multiply($seller->getVatRate());

        $order = new Order(
            $creationFeeTransaction->reference_number,
            [
                new PurchaseLine(
                    __('zatca/e-invoice.create_order_cost', [
                        'trader_order_id' => $traderOrder->getKey(),
                        'financing_order_id' => $traderOrder->financing_order_id,
                    ]),
                    $lender->order_cost,
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
            $lender->order_cost->add($vatAmount)->convertAndFormatByDecimal(),
            $vatAmount->convertAndFormatByDecimal(),
            $order,
            $lender->name,
            $creationFeeTransaction
        );

        $this->generateZatcaInvoice->handle($invoiceSpecs);
    }
}
