<?php

namespace App\Transformers;

use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Models\FinancingOrder;
use App\Models\Transaction;
use App\Support\Wallets\Contracts\TransactionUtilInterface;
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
        'receipt',
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
            ? app(TransactionUtilInterface::class)->getDescription($transaction)
            : null
        );
    }

    public function includeAmount(Transaction $transaction): Primitive
    {
        return $this->primitive($transaction->amount->formatByDecimal());
    }

    public function includeReceipt(Transaction $transaction): Primitive
    {
        return $this->primitive(
            isset($transaction->meta['financing_order_id'])
                ? FinancingOrder::query()->find($transaction->meta['financing_order_id'])
                    ->getFirstMedia(TransactionMediaCollection::Attachments)
                    ?->fileUrl()
                : null
        );
    }
}
