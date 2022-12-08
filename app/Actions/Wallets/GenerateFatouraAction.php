<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\GenerateFatoura;
use App\Enums\MediaCollections\FaturaMediaCollection;
use App\Models\FinancingOrder;
use App\Models\Transaction;
use App\Support\PdfGenerator\PdfGenerator;
use Illuminate\Support\Facades\DB;

class GenerateFatouraAction implements GenerateFatoura
{
    protected string $template = 'templates.zatca-invoice';

    protected string $collectionName = FaturaMediaCollection::TRANSACTIONS;

    public function __construct(protected GetProjectSettings $getProjectSettings)
    {
    }

    public function handel(FinancingOrder $financingOrder, Transaction $creationFeeTransaction, Transaction $vatPercentageTransaction)
    {
        $seller = $this->getProjectSettings->handle();

        DB::transaction(function () use ($financingOrder, $seller, $creationFeeTransaction, $vatPercentageTransaction) {
            $displayQRCodeAsBase64 = \GenerateQrCode::size(120)->eyeColor(0, 5, 124, 148, 0, 0, 0)->generate("
               Company name: {$seller->getCompanyName()}, \r\n
               VAT ID: {$seller->getVatId()}, \r\n
               Order Date: {$financingOrder->created_at}, \r\n
               Total Amount: {$financingOrder->amount->formatByDecimal()}, \r\n
               Tax: {$financingOrder->amount->multiply($seller->getVatRate())->formatByDecimal()}"
            );

            $html = view($this->getTemplate(), [
                'seller' => $seller,
                'order' => $financingOrder,
                'qr_code' => $displayQRCodeAsBase64,
                'buyer' => $financingOrder->company,
                'creationFeeTransaction' => $creationFeeTransaction,
                'vatPercentageTransaction' => $vatPercentageTransaction,
            ])->render();

            $path = "zatca-{$financingOrder->reference_number}.pdf";

            PdfGenerator::outputFromHtml($html, $path, function ($fileResource) use ($financingOrder) {
                return $financingOrder->addMediaFromStream($fileResource)
                    ->usingFileName("zatca-{$financingOrder->getNationalId()}".'.pdf')
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
