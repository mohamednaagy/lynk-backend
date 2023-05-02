<?php

namespace App\Jobs;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\ZatcaEInvoice\Order;
use App\Support\ZatcaEInvoice\PurchaseLine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

class OverwriteZatcaInvoiceMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $template = 'templates.zatca-invoice';

    protected string $collectionName = FinancingOrderMediaCollection::ZatcaInvoice;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $seller = app(GetProjectSettings::class)->handle();

        FinancingOrder::query()
            ->orderBy('id')
            ->chunk(100, function ($orders) use ($seller) {
                $orders->map(function ($financingOrder) use ($seller) {
                    $creationFeeTransaction = $financingOrder->creationFeeTransactions?->first();

                    if (blank($creationFeeTransaction)) {
                        return;
                    }

                    $media = $financingOrder->getFirstMedia(FinancingOrderMediaCollection::ZatcaInvoice);

                    if ($media) {
                        $financingOrder->clearMediaCollection(FinancingOrderMediaCollection::ZatcaInvoice);
                    }

                    DB::transaction(function () use ($financingOrder, $seller, $creationFeeTransaction) {
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
                                        $financingOrder->company->order_cost,
                                        $seller->getVatRateInPercentage()
                                    ),
                                ],
                                $financingOrder->created_at,
                                $financingOrder
                            ),
                            'qr_code' => $displayQRCodeAsBase64,
                            'buyer' => $financingOrder->company,
                            'creation_fee_transaction' => $creationFeeTransaction,
                        ])->render();

                        PdfGenerator::outputFromHtml(
                            $html,
                            function ($fileResource) use ($financingOrder) {
                                return $financingOrder->addMediaFromStream($fileResource)
                                    ->usingFileName("zatca-{$financingOrder->getKey()}".'.pdf')
                                    ->toMediaCollection($this->getCollectionName());
                            }
                        );
                    });
                });
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
