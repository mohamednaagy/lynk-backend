<?php

namespace App\Support\DocumentEngine\Generators;

use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Support\DocumentEngine\BasePdfGenerator;
use App\Support\DocumentEngine\Traits\HasMoney;
use App\Support\DocumentEngine\Traits\HasTransaction;

class VoucherReceiptPdf extends BasePdfGenerator
{
    use HasMoney, HasTransaction;

    protected $collectionName = TransactionMediaCollection::VoucherReceipt;

    protected string $timezone = 'Asia/Riyadh';

    public function getStorageCallback(): callable
    {
        $transaction = $this->getTransaction();

        return function ($fileResource) use ($transaction) {
            return $transaction->addMediaFromStream($fileResource)
                ->usingFileName("voucher-receipt-{$transaction->reference_number}".'.pdf')
                ->toMediaCollection($this->collectionName);
        };
    }

    public function isGeneratedBefore(): bool
    {
        return $this->getTransaction()->hasMedia($this->collectionName);
    }

    public function getGeneratedBeforePath(): string
    {
        return $this->getTransaction()->getMedia($this->collectionName)->first()->getPath();
    }

    protected function prepareData(): array
    {
        $transaction = $this->getTransaction();
        $amount = $this->getAmount();
        $company = $transaction->wallet->holder;

        $content = __('invoices/voucher-receipt.content', [
            'amount' => $amount->convertAndFormatByDecimal(sperator: ','),
            'company_name' => $company->name,
        ], 'ar');

        return [
            'day' => $transaction->created_at->tz($this->timezone)->locale('ar')->dayName,
            'date' => $transaction->created_at->tz($this->timezone)->toDateString(),
            'time' => $transaction->created_at->tz($this->timezone)->toTimeString(),
            'content' => $content,
        ];
    }

    protected function getTemplatePath(): string
    {
        return 'pdf.voucher_receipt';
    }
}
