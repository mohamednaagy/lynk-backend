<?php

namespace App\Jobs;

use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Models\FinancingOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OverwriteZatcaInvoiceMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $template = 'templates.zatca-invoice';

    protected string $collectionName = FinancingOrderMediaCollection::ZatcaInvoice;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
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
        FinancingOrder::query()
            ->orderBy('id')
            ->chunk(100, function ($orders) {
                $orders->map(function ($financingOrder) {
                    // Skip if the company is soft deleted
                    if (is_null($financingOrder->company)) {
                        return;
                    }

                    $creationFeeTransaction = $financingOrder->creationFeeTransactions?->first();

                    if (blank($creationFeeTransaction)) {
                        return;
                    }

                    $media = $financingOrder->getFirstMedia(FinancingOrderMediaCollection::ZatcaInvoice);

                    if ($media) {
                        $financingOrder->clearMediaCollection(FinancingOrderMediaCollection::ZatcaInvoice);
                    }

                    app(GenerateZatcaInvoice::class)->handle($financingOrder, $creationFeeTransaction);
                });
            });
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getCollectionName()
    {
        return $this->collectionName;
    }
}
