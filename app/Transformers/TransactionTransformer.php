<?php

namespace App\Transformers;

use App\Models\Transaction;
use App\Support\Transactions\Descriptions\DescriptionManager;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;

class TransactionTransformer extends TransformerAbstract
{
    // __REVIEW__ move transform values to $availableIncludes
    public function transform(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'date' => Carbon::parse($transaction->created_at)->format('Y-m-d'),
            'description' => ! is_null($transaction->reason)
                ? DescriptionManager::getDescription($transaction)
                : null,
            'amount' => $transaction->amount->formatByDecimal(),
        ];
    }
}
