<?php

namespace App\Jobs;

use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class GenerateZatcaInvoiceIfMissing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $modelAndCollection = [
        FinancingOrder::class => FinancingOrderMediaCollection::ZatcaInvoice,
        TraderOrder::class => TraderOrderMediaCollection::ZatcaInvoice,
    ];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected Transaction $transaction)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $modelClass = FinancingOrder::class;
            $modelId = Arr::get($this->transaction->meta, 'financing_order_id');

            if (array_key_exists('trader_order_id', $this->transaction->meta)) {
                $modelClass = TraderOrder::class;
                $modelId = Arr::get($this->transaction->meta, 'trader_order_id');
            }

            $model = $modelClass::find($modelId);

            $media = $model->getFirstMedia($this->modelAndCollection[$modelClass]);

            if ($media) {
                return;
            }

            $traderOrder = $model;

            if ($model instanceof FinancingOrder) {
                $traderOrder = $model->traderOrders()->first();
            }

            if ($traderOrder === null) {
                return;
            }

            app(GenerateZatcaInvoice::class)->handle(
                $traderOrder,
                creationFeeTransaction: $this->transaction
            );
        } catch (\Throwable $th) {
            throw new \Exception(sprintf('Transaction #%s cannot create zatca', $this->transaction->id), 0, $th);
        }
    }
}
