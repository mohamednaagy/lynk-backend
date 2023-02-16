<?php

namespace App\Support\Collections;

use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\TransactionReason;
use App\Models\FinancingOrder;
use App\Models\Media;
use Illuminate\Database\Eloquent\Collection;

class TransactionCollection extends Collection
{
    public function loadZatcaInvoicesMedia()
    {
        $financingOrderIdsCollection = $this->whereIn(
            'reason',
            TransactionReason::$reasonsAssociatedWithZatcaInvoice
        )
            ->pluck('meta.financing_order_id')
            ->unique();

        $mediaKeyedByModelId = Media::where([
            ['model_type', (new FinancingOrder())->getMorphClass()],
            ['collection_name', FinancingOrderMediaCollection::ZatcaInvoice],
        ])
            ->whereIn('model_id', $financingOrderIdsCollection->toArray())
            ->get()
            ->keyBy('model_id');

        $this->transform(function ($transaction) use ($mediaKeyedByModelId) {
            if (
                in_array($transaction->reason, TransactionReason::$reasonsAssociatedWithZatcaInvoice)
                && array_key_exists('financing_order_id', $transaction->meta)
            ) {
                $mediaModel = $mediaKeyedByModelId->get($transaction->meta['financing_order_id']);

                $transaction->setAttribute(
                    'zatca_invoice_media',
                    $mediaModel
                );
            }

            return $transaction;
        });
    }
}
