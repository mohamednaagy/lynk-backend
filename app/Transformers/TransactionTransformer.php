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
    protected ?string $area = null;

    protected array $availableIncludes = [
        'id',
        'date',
        'description',
        'amount',
        'amount_formatted',
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
        return $this->primitive($transaction->created_at?->format('Y-m-d'));
    }

    public function includeDescription(Transaction $transaction): Primitive
    {
        return $this->primitive(
            ! is_null($transaction->reason)
                ? app(TransactionUtilInterface::class)->getDescription($transaction)
                : null
        );
    }

    public function includeAmountFormatted(Transaction $transaction): Primitive
    {
        return $this->primitive(number_format($transaction->amount->formatByDecimal(), 2));
    }

    public function includeAmount(Transaction $transaction): Primitive
    {
        return $this->primitive($transaction->amount->formatByDecimal());
    }

    public function includeReceiptUrl(Transaction $transaction): Primitive|NullResource
    {
        if (
            $transaction->reason === TransactionReason::OrderCreationFee
        ) {
            if ($transaction->meta['is_vat_included'] === false) {
                return $this->primitive(optional($transaction->zatcaInvoiceMedia)->file_url);
            }

            return $this->primitive(
                $transaction->getFirstMedia(TransactionMediaCollection::ZatcaInvoice)?->file_url
            );
        } elseif ($transaction->reason === TransactionReason::ManualDeposit) {
            return $this->primitive(
                $transaction->getFirstMedia(TransactionMediaCollection::VoucherReceipt)?->file_url
            );
        } elseif (in_array($transaction->reason, [TransactionReason::VatPercentageFee, TransactionReason::VatPercentageOnDeposit])) {
            return $this->primitive(
                $transaction->getFirstMedia(TransactionMediaCollection::ZatcaInvoice)?->file_url
            );
        } elseif ($transaction->reason === TransactionReason::DepositByEdaat) {
            $fileUrl = null;

            if ($media = $transaction->getFirstMedia(TransactionMediaCollection::VoucherReceipt)) {
                $fileUrl = $media->file_url;
            } elseif ($transaction->zatcaInvoiceMedia) {
                $fileUrl = $transaction->zatcaInvoiceMedia->file_url;
            }

            return $this->primitive($fileUrl);
        }

        return $this->null();
    }
}
