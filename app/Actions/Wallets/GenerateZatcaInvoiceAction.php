<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Enums\MediaCollections\ZatcaInvoiceMediaCollection;
use App\Models\FinancingOrder;
use App\Models\Transaction;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\ZatcaEInvoice\Order;
use App\Support\ZatcaEInvoice\PurchaseLine;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

class GenerateZatcaInvoiceAction implements GenerateZatcaInvoice
{
    protected string $template = 'templates.zatca-invoice';

    protected string $collectionName = ZatcaInvoiceMediaCollection::Transactions;

    public function __construct(protected GetProjectSettings $getProjectSettings)
    {
    }

    public function handel(FinancingOrder $financingOrder, Transaction $creationFeeTransaction, Transaction $vatPercentageTransaction)
    {
        $seller = $this->getProjectSettings->handle();

        DB::transaction(function () use ($financingOrder, $seller, $creationFeeTransaction, $vatPercentageTransaction) {
            $displayQRCodeAsBase64 = GenerateQrCode::fromArray([
                new Seller($seller->getCompanyName(Config::get('app.locale', 'en'))),
                new TaxNumber($seller->getVatId()),
                new InvoiceDate($financingOrder->created_at),
                new InvoiceTotalAmount($financingOrder->amount->formatByDecimal()),
                new InvoiceTaxAmount($financingOrder->amount->multiply($seller->getVatRate())->formatByDecimal()),
            ])->render();

            $html = view($this->getTemplate(), [
                'seller' => $seller,
                'order' => new Order(
                    $creationFeeTransaction->reference_number,
                    [
                        new PurchaseLine(
                            __('zatca/e-invoice.create_order_cost', [
                                'number' => $financingOrder->getKey(),
                            ]),
                            tenant()->order_cost,
                            $seller->getVatRateInPercentage()
                        ),
                    ],
                    now('Asia/Riyadh'),
                    $financingOrder
                ),
                'qr_code' => $displayQRCodeAsBase64,
                'buyer' => $financingOrder->company,
                'creationFeeTransaction' => $creationFeeTransaction,
                'vatPercentageTransaction' => $vatPercentageTransaction,
            ])->render();

            PdfGenerator::outputFromHtml($html, function ($fileResource) use ($financingOrder) {
                return $financingOrder->addMediaFromStream($fileResource)
                    ->usingFileName("zatca-{$financingOrder->getKey()}".'.pdf')
                    ->toMediaCollection($this->getCollectionName());
            }
            );
        });
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
