<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Support\Transactions\Descriptions\DescriptionManager;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Http\UploadedFile;

class CreateTransactionsAction implements CreateTransactions
{
    /**
     * @param  Wallet  $wallet
     * @param  int  $transactionReason
     * @param  string  $amount
     * @param  array  $meta
     * @return string
     */
    public function handle(
        Wallet $wallet,
        int $transactionReason,
        string $amount,
        array $meta,
        array $attachments
    ) {
        $transaction = DescriptionManager::handleTransaction($transactionReason, $wallet, $amount, $meta);

        foreach ($attachments as $media) {
            if ($media instanceof UploadedFile) {
                $transaction->addMedia($media)->toMediaCollection(TransactionMediaCollection::Attachments);
            }
        }

        return $transaction;
    }
}
