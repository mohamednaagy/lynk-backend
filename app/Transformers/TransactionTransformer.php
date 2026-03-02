<?php

namespace App\Transformers;

use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\TransactionReason;
use App\Models\Transaction;
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
        'balance_formatted',
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
            $transaction->description
        );
    }

    public function includeAmountFormatted(Transaction $transaction): Primitive
    {
        return $this->primitive($transaction->amount->convertAndFormatByDecimal(separator: ','));
    }

    public function includeAmount(Transaction $transaction): Primitive
    {
        return $this->primitive($transaction->amount->convertAndFormatByDecimal());
    }

    public function includeBalanceFormatted(Transaction $transaction): Primitive
    {
        return $this->primitive($transaction->balance->convertAndFormatByDecimal(separator: ','));
    }

    public function includeReceiptUrl(Transaction $transaction): Primitive|NullResource
    {
        if (
            $transaction->reason === TransactionReason::OrderCreationFee
        ) {
            if ($transaction->meta['is_vat_included'] === false) {
                return $this->primitive(optional($transaction->zatcaInvoiceMedia)->file_url);
            }

            /** @var \App\Models\Media|null $media */
            $media = $transaction->getFirstMedia(TransactionMediaCollection::ZatcaInvoice);

            return $this->primitive($media?->file_url);
        } elseif ($transaction->reason === TransactionReason::ManualDeposit) {
            /** @var \App\Models\Media|null $media */
            $media = $transaction->getFirstMedia(TransactionMediaCollection::VoucherReceipt);

            return $this->primitive($media?->file_url);
        } elseif (in_array($transaction->reason, [TransactionReason::VatPercentageFee, TransactionReason::VatPercentageOnDeposit])) {
            /** @var \App\Models\Media|null $media */
            $media = $transaction->getFirstMedia(TransactionMediaCollection::ZatcaInvoice);

            return $this->primitive($media?->file_url);
        } elseif ($transaction->reason === TransactionReason::DepositByEdaat) {
            $fileUrl = null;

            /** @var \App\Models\Media|null $media */
            $media = $transaction->getFirstMedia(TransactionMediaCollection::VoucherReceipt);

            if ($media) {
                $fileUrl = $media->file_url;
            } elseif ($transaction->zatcaInvoiceMedia) {
                $fileUrl = $transaction->zatcaInvoiceMedia->file_url;
            }

            return $this->primitive($fileUrl);
        }

        return $this->null();
    }
}
