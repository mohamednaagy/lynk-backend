<?php

namespace App\Transformers;

use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\TransactionReason;
use App\Models\Transaction;
use App\Support\Wallets\Contracts\TransactionUtilInterface;
use League\Fractal\Resource\NullResource;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class TransactionTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'date',
        'description',
        'amount',
        'receipt_url',
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
        return $this->primitive($transaction->created_at->format('Y-m-d'));
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

    public function includeReceiptUrl(Transaction $transaction): Primitive|NullResource
    {
        if (in_array($transaction->reason, TransactionReason::$reasonsAssociatedWithZatcaInvoice)) {
            return $this->primitive(
                $transaction->zatca_invoice_media?->file_url
            );
        } elseif (in_array($transaction->reason, [TransactionReason::DepositByEdaat, TransactionReason::ManualDeposit])) {
            return $this->primitive(
                $transaction->getFirstMedia(TransactionMediaCollection::VoucherReceipt)?->file_url
            );
        } elseif (in_array($transaction->reason, [TransactionReason::VatPercentageOnDeposit])) {
            return $this->primitive(
                $transaction->getFirstMedia(TransactionMediaCollection::RechargeReceipt)?->file_url
            );
        }

        return $this->null();
    }
}
