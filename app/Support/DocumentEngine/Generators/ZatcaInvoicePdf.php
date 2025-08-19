<?php

namespace App\Support\DocumentEngine\Generators;

use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Models\ZatcaInvoice;
use App\Support\DocumentEngine\BasePdfGenerator;
use App\Support\DocumentEngine\Traits\HasInvoiceSpecs;
use App\Support\ZatcaEInvoice\InvoiceSpecs;
use Illuminate\Support\Facades\Config;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

class ZatcaInvoicePdf extends BasePdfGenerator
{
    use HasInvoiceSpecs;

    protected $invoiceId;

    protected string $collectionName = TransactionMediaCollection::ZatcaInvoice;

    public function getStorageCallback(): callable
    {
        $invoiceSpecs = $this->getInvoiceSpecs();

        return function ($fileResource) use ($invoiceSpecs) {
            return $invoiceSpecs->getAssociatedModel()
                ->addMediaFromStream($fileResource)
                ->usingFileName("simplified-invoice-{$this->invoiceId}".'.pdf')
                ->toMediaCollection($this->collectionName);
        };
    }

    public function isGeneratedBefore(): bool
    {
        return $this->getInvoiceSpecs()->getAssociatedModel()->hasMedia($this->collectionName);
    }

    public function getGeneratedBeforePath(): string
    {
        return $this->getInvoiceSpecs()->getAssociatedModel()->getMedia($this->collectionName)->first()->getPath();
    }

    protected function prepareData(): array
    {
        $invoiceSpecs = $this->getInvoiceSpecs();

        return [
            'invoice_number' => $this->invoiceId,
            'seller' => $invoiceSpecs->getSeller(),
            'order' => $invoiceSpecs->getOrder(),
            'qr_code' => $this->generateQRCode($invoiceSpecs),
            'buyer' => $invoiceSpecs->getBuyer(),
            'creation_fee_transaction' => $invoiceSpecs->getTransaction(),
        ];
    }

    protected function getTemplatePath(): string
    {
        return 'templates.zatca-invoice';
    }

    private function generateQRCode(InvoiceSpecs $invoiceSpecs)
    {
        return GenerateQrCode::fromArray([
            new Seller($invoiceSpecs->getSeller()->getCompanyName(Config::get('app.locale', 'en'))),
            new TaxNumber($invoiceSpecs->getTaxNumber()),
            new InvoiceDate($invoiceSpecs->getDate()),
            new InvoiceTotalAmount($invoiceSpecs->getTotalAmountWithVat()),
            new InvoiceTaxAmount($invoiceSpecs->getVatAmount()),
        ])->render();
    }

    private function generateInvoiceNumber(InvoiceSpecs $invoiceSpecs)
    {
        if ($this->invoiceId) {
            return $this->invoiceId;
        }

        return ZatcaInvoice::query()->create([
            'transaction_id' => $invoiceSpecs->getTransaction()->getKey(),
        ])->getKey();
    }

    public function beforeGenerate(): void
    {
        $this->generateInvoiceNumber($this->getInvoiceSpecs());
    }
}
