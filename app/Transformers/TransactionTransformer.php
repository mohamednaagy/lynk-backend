<?php

namespace App\Transformers;

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

    // __IMPROVE__ we need to add a property called "receipt" and it will return a link in case
    // if the transaction is order creation fee or vat fee
    // the link will download the zatca invoice
}
