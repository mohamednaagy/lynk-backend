<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\GenerateVoucherReceipt;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Models\Transaction;
use App\Support\PdfGenerator\PdfGenerator;

class GenerateVoucherReceiptAction implements GenerateVoucherReceipt
{
    protected string $timezone = 'Asia/Riyadh';

    protected string $template = 'templates.voucher-invoice';

    protected string $collectionName = TransactionMediaCollection::VoucherReceipt;

    public function handle(Transaction $transaction)
    {
        $company = $transaction->wallet->holder;
        $amount = $transaction->amount->formatByDecimal();

        if (isset($transaction->meta['voucher_value'])) {
            $amount = $transaction->meta['voucher_value'];
        }

        $content = __('invoices/voucher-receipt.content', [
            'amount' => $amount,
            'company_name' => $company->name,
        ], 'ar');

        $html = view($this->getTemplate(), [
            'day' => $transaction->created_at->tz($this->timezone)->locale('ar')->dayName,
            'date' => $transaction->created_at->tz($this->timezone)->toDateString(),
            'time' => $transaction->created_at->tz($this->timezone)->toTimeString(),
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
                    ->usingFileName("voucher-receipt-{$transaction->getKey()}".'.pdf')
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
