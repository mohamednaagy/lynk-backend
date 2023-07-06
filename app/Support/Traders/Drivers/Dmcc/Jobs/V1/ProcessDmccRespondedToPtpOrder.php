<?php

namespace App\Support\Traders\Drivers\Dmcc\Jobs\V1;

use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccRespondedToPtpOrder implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TraderHelperTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected $traderOrderId)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        // to unify
        DB::transaction(function () {
            $traderOrder = TraderOrder::query()->lockForUpdate()->findOrFail($this->traderOrderId);

            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::RespondPtp)) {
                return;
            }

            $trader = Trader::driver($traderOrder->provider);

            $ptpDocument = $trader->getDocumentByTypeAndTransaction(
                $traderOrder->reference,
                'Promise to Purchase'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetPtpDocument
            );

            $this->attachDocumentToOrder(
                $traderOrder,
                $ptpDocument,
                TraderOrderMediaCollection::PromiseToPurchase,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachPtpDocumentToOrder
            );

            $ttiDocument = $trader->getDocumentByTypeAndTransaction(
                $traderOrder->reference,
                'TTI - Holding certificate'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetTtiHoldingCertificateDocument
            );

            $this->attachDocumentToOrder(
                $traderOrder,
                $ttiDocument,
                TraderOrderMediaCollection::TtiHoldingCertificate,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachTtiHoldingCertificateDocument
            );

            app(GenerateClientWakala::class)->handle($traderOrder);
        });
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrderId;
    }
}
