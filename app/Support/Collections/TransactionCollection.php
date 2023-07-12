<?php

namespace App\Support\Collections;

use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TransactionReason;
use App\Models\Media;
use App\Models\TraderOrder;
use Illuminate\Database\Eloquent\Collection;

class TransactionCollection extends Collection
{
    public function loadZatcaInvoicesMedia()
    {
        $traderOrderIdsCollection = $this->whereIn(
            'reason',
            TransactionReason::$reasonsAssociatedWithZatcaInvoice
        )
            ->pluck('meta.trader_order_id')
            ->unique();

        $mediaKeyedByModelId = Media::where([
            ['model_type', (new TraderOrder())->getMorphClass()],
            ['collection_name', TraderOrderMediaCollection::ZatcaInvoice],
        ])
            ->whereIn('model_id', $traderOrderIdsCollection->toArray())
            ->get()
            ->keyBy('model_id');

        $this->transform(function ($transaction) use ($mediaKeyedByModelId) {
            if (
                in_array($transaction->reason, TransactionReason::$reasonsAssociatedWithZatcaInvoice)
                && array_key_exists('trader_order_id', $transaction->meta)
            ) {
                $mediaModel = $mediaKeyedByModelId->get($transaction->meta['trader_order_id']);

                $transaction->setAttribute(
                    'zatca_invoice_media',
                    $mediaModel
                );
            }

            return $transaction;
        });
    }
}
