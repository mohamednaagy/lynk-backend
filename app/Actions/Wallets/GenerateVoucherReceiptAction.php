<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\GenerateVoucherReceipt;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Models\Transaction;
use App\Support\PdfGenerator\PdfGenerator;

class GenerateVoucherReceiptAction implements GenerateVoucherReceipt
{
    protected string $template = 'templates.voucher-invoice';

    protected string $collectionName = TransactionMediaCollection::VoucherReceipt;

    public function handle(Transaction $transaction)
    {
        app()->setLocale('ar');
        $company = $transaction->wallet->holder;
        $content = __('invoices/voucher-receipt.content', [
            'amount' => $transaction->amount->formatByDecimal(),
            'company_name' => $company->name,
        ]);

        $html = view($this->getTemplate(), [
            'day' => $transaction->created_at->tz('Asia/Riyadh')->locale('ar')->dayName,
            'date' => $transaction->created_at->tz('Asia/Riyadh')->toDateString(),
            'time' => $transaction->created_at->tz('Asia/Riyadh')->toTimeString(),
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

    public function getTemplate()
    {
        return $this->template;
    }

    public function getCollectionName()
    {
        return $this->collectionName;
    }
}
