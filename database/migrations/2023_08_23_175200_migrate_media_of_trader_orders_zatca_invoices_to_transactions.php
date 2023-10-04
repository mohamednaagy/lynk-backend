<?php

use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\TransactionReason;
use App\Models\FinancingOrder;
use App\Models\Media;
use App\Models\TraderOrder;
use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Media::whereIn(
            'collection_name',
            [
                FinancingOrderMediaCollection::ZatcaInvoice,
                TraderOrderMediaCollection::ZatcaInvoice,
            ],
        )
            ->whereIn(
                'model_type',
                [
                    FinancingOrder::class,
                    TraderOrder::class,
                ]
            )
            ->withTrashed()
            ->orderBy('id')
            ->chunk(100, function ($media) {
                foreach ($media as $mediaItem) {
                    DB::transaction(function () use ($mediaItem) {
                        $financingOrderId = null;

                        if ($mediaItem->model_type === FinancingOrder::class) {
                            $financingOrderId = $mediaItem->model_id;
                        } elseif ($mediaItem->model_type === TraderOrder::class) {
                            if ($mediaItem->model) {
                                $financingOrderId = $mediaItem->model->financing_order_id;
                            } else {
                                return;
                            }
                        }

                        // if ($financingOrderId === null) {
                        //     return;
                        // }

                        $transaction = Transaction::where('meta->financing_order_id', $financingOrderId)
                            ->where('reason', TransactionReason::VatPercentageFee)
                            ->first();

                        // if ($transaction === null) {
                        //     return;
                        // }

                        $mediaItem->update([
                            'model_type' => $transaction->getMorphClass(),
                            'model_id' => $transaction->id,
                            'collection_name' => TransactionMediaCollection::ZatcaInvoice,
                        ]);
                    });
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
