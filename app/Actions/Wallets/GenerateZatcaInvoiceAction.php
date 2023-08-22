<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Models\ZatcaInvoice;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\ZatcaEInvoice\InvoiceSpecs;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

class GenerateZatcaInvoiceAction implements GenerateZatcaInvoice
{
    protected string $template = 'templates.zatca-invoice';

    public function __construct(protected GetProjectSettings $getProjectSettings)
    {
    }

    public function handle(InvoiceSpecs $invoiceSpecs, $mediaCollection)
    {
        $invoiceId = ZatcaInvoice::query()->create([
            'transaction_id' => $invoiceSpecs->getTransaction()->getKey(),
        ])->getKey();

        $displayQRCodeAsBase64 = GenerateQrCode::fromArray([
            new Seller($invoiceSpecs->getSeller()),
            new TaxNumber($invoiceSpecs->getTaxNumber()),
            new InvoiceDate($invoiceSpecs->getDate()),
            new InvoiceTotalAmount($invoiceSpecs->getTotalAmountWithVat()),
            new InvoiceTaxAmount($invoiceSpecs->getVatAmount()),
        ])->render();

        $html = view($this->getTemplate(), [
            'invoice_number' => $invoiceId,
            'seller' => $this->getProjectSettings->handle(),
            'order' => $invoiceSpecs->getOrder(),
            'qr_code' => $displayQRCodeAsBase64,
            'buyer' => $invoiceSpecs->getBuyer(),
            'creation_fee_transaction' => $invoiceSpecs->getTransaction(),
        ])->render();

        PdfGenerator::outputFromHtml(
            $html,
            function ($fileResource) use ($invoiceSpecs, $mediaCollection, $invoiceId) {
                return $invoiceSpecs->getAssociatedModel()->addMediaFromStream($fileResource)
                    ->usingFileName("simplified-invoice-{$invoiceId}".'.pdf')
                    ->toMediaCollection($mediaCollection);
            }
        );
    }

    public function getTemplate()
    {
        return $this->template;
    }
}
