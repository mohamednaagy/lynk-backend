<?php

namespace App\Transformers;

use App\Support\Transactions\Descriptions\DescriptionManager;
use Bavix\Wallet\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class TransactionTransformer extends TransformerAbstract
{
    public function transform(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'date' => Carbon::parse($transaction->created_at)->format('Y-m-d'),
            'description' => ! is_null($transaction->meta) && ! is_null($transaction->meta['description'])
                ? DescriptionManager::getDescription($transaction)
                : null,
            'amount' => $transaction->amount,
            'balance' => ! is_null($transaction->meta)
                ? Arr::get($transaction->meta, 'balance')
                : null,
        ];
    }
}
