<?php

namespace App\Transformers;

use App\Models\Transaction;
use App\Support\Transactions\Descriptions\DescriptionManager;
use Carbon\Carbon;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class TransactionTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'date',
        'description',
        'amount',
    ];

    public function transform(Transaction $transaction): array
    {
        return [];
    }

    public function includeId(Transaction $transaction): Primitive
    {
        return $this->primitive($transaction->id);
    }

    public function includeDate(Transaction $transaction): Primitive
    {
        return $this->primitive(Carbon::parse($transaction->created_at)->format('Y-m-d'));
    }

    public function includeDescription(Transaction $transaction): Primitive
    {
        return $this->primitive(
            ! is_null($transaction->reason)
            ? DescriptionManager::getDescription($transaction)
            : null
        );
    }

    public function includeAmount(Transaction $transaction): Primitive
    {
        return $this->primitive($transaction->amount->formatByDecimal());
    }
}
