<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\GenerateVoucherInvoice;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Models\Company;
use App\Models\Transaction;
use App\Support\PdfGenerator\PdfGenerator;
use Money\Money;

class GenerateVoucherInvoiceAction implements GenerateVoucherInvoice
{
    protected string $template = 'templates.voucher-invoice';

    protected string $collectionName = TransactionMediaCollection::VoucherInvoice;

    public function handle(Transaction $transaction)
    {
        $company = $transaction->wallet->holder;
        $availableOrdersCount = $this->getAvailableOrdersCount($company, $transaction);

        $content = __('invoice/voucher.content', [
            'amount' => $transaction->amount->formatByDecimal(),
            'company_name' => $company->name,
            'number' => $availableOrdersCount,
        ]);

        $html = view($this->getTemplate(), [
            'day' => $transaction->created_at->isoFormat('dddd'),
            'date' => $transaction->created_at->toDateString(),
            'time' => $transaction->created_at->toTimeString(),
            'content' => $content,
        ])->render();

        $this->generatePdfFile($html, $transaction);
    }

    protected function generatePdfFile($html, $transaction)
    {
        return PdfGenerator::outputFromHtml(
            $html,
            function ($fileResource) use ($transaction) {
                return $transaction->addMediaFromStream($fileResource)
                    ->usingFileName("voucher-{$transaction->getKey()}".'.pdf')
                    ->toMediaCollection($this->getCollectionName());
            }
        );
    }

    private function getAvailableOrdersCount(Company $company, Transaction $transaction)
    {
        $vatRate = app(GetProjectSettings::class)->handle()->getVatRate();
        $orderCost = $company->order_cost->multiply(($vatRate) + 1)->getAmount();

        return $transaction->amount->divide($orderCost, Money::ROUND_DOWN)->getAmount();
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
