<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Models\Transaction;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\ZatcaEInvoice\Order;
use App\Support\ZatcaEInvoice\PurchaseLine;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Config;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

class GenerateZatcaInvoiceAction implements GenerateZatcaInvoice
{
    protected string $template = 'templates.zatca-invoice';

    protected string $collectionName = TraderOrderMediaCollection::ZatcaInvoice;

    public function __construct(protected GetProjectSettings $getProjectSettings)
    {
    }

    public function handel(TraderOrder $traderOrder, Transaction $creationFeeTransaction)
    {
        $seller = $this->getProjectSettings->handle();
        $financingOrder = $traderOrder->order;
        $financingOrder->load([
            'company' => fn ($query) => $query->withoutGlobalScope(SoftDeletingScope::class),
        ]);

        $company = $financingOrder->company()->withTrashed()->first();

        $vatAmount = $company->order_cost->multiply($seller->getVatRate());

        $displayQRCodeAsBase64 = GenerateQrCode::fromArray([
            new Seller($seller->getCompanyName(Config::get('app.locale', 'en'))),
            new TaxNumber($seller->getVatId()),
            new InvoiceDate($traderOrder->created_at->timezone('Asia/Riyadh')->toDateTimeString()),
            new InvoiceTotalAmount($company->order_cost->add($vatAmount)->formatByDecimal()),
            new InvoiceTaxAmount($vatAmount->formatByDecimal()),
        ])->render();

        $html = view($this->getTemplate(), [
            'seller' => $seller,
            'order' => new Order(
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
            ),
            'qr_code' => $displayQRCodeAsBase64,
            'buyer' => $company,
            'creation_fee_transaction' => $creationFeeTransaction,
        ])->render();

        PdfGenerator::outputFromHtml(
            $html,
            function ($fileResource) use ($traderOrder) {
                return $traderOrder->addMediaFromStream($fileResource)
                    ->usingFileName("simplified-invoice-{$traderOrder->getKey()}".'.pdf')
                    ->toMediaCollection($this->getCollectionName());
            }
        );
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getCollectionName()
    {
        return $this->collectionName;
    }
}
