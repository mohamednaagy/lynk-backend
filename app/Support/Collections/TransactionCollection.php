<?php

namespace App\Support\Collections;

use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\TransactionReason;
use App\Models\Media;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

class TransactionCollection extends Collection
{
    public function loadZatcaInvoicesMedia()
    {
        $transactionsWithoutInvoicesReferences = $this->filter(function ($transaction) {

            return $transaction->reason === TransactionReason::DepositByEdaat
                || ($transaction->reason === TransactionReason::OrderCreationFee
                    && $transaction->meta['is_vat_included'] === false);
        })
            ->pluck('reference_number');

        $relatedTransactions = Transaction::whereIn('reference_number', $transactionsWithoutInvoicesReferences)
            ->whereIn('reason', [TransactionReason::VatPercentageFee, TransactionReason::VatPercentageOnDeposit])
            ->get();

        $mediaKeyedByModelId = Media::where('model_type', (new Transaction())->getMorphClass())
            ->whereIn('model_id', $relatedTransactions->pluck('id'))
            ->where('collection_name', TransactionMediaCollection::ZatcaInvoice)
            ->get()
            ->keyBy('model_id');

        $relatedTransactionsKeyedByRef = $relatedTransactions->keyBy('reference_number');

        $this->transform(function ($transaction) use (
            $relatedTransactionsKeyedByRef,
            $mediaKeyedByModelId
        ) {
            if (
                $transaction->reason === TransactionReason::DepositByEdaat
                || ($transaction->reason === TransactionReason::OrderCreationFee
                    && $transaction->meta['is_vat_included'] === false)
            ) {
                $relatedTransaction = $relatedTransactionsKeyedByRef[$transaction->reference_number] ?? null;

                $media = null;

                if ($relatedTransaction !== null) {
                    $media = $mediaKeyedByModelId[$relatedTransaction->id] ?? null;
                }

                $transaction->setAttribute('zatcaInvoiceMedia', $media);
            }

            return $transaction;
        });
    }
}
