<?php

namespace App\Jobs\Dmcc;

use App\Actions\Contracts\Clients\AskClientWakala;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class ProcessClientWakalaCompletedOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected mixed $financingOrder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($financingOrder)
    {
        $this->financingOrder = $financingOrder;
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        /** @var FinancingOrder $financingOrder */
        $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($this->financingOrder);
        $lastTraderOrder = $financingOrder->activeTraderOrder()
            ->whereIn('provider', ['dmcc', 'fake'])->first();
        $trader = Trader::driver($lastTraderOrder->provider);

        $mpoDocument = $trader->getDocumentByTypeAndTransaction(
            $lastTraderOrder->reference,
            'Murabaha Purchase Offer Document'
        );

        $trader->createTraderOrderHistory(
            $lastTraderOrder,
            FinancingOrderHistory::GetMurabahaPurchaseOfferDocument
        );

        $trader->attachDocumentToOrder(
            $lastTraderOrder,
            $mpoDocument,
            FinancingOrderMediaCollection::MurabahaPurchaseOrder,
            'base64'
        );

        $trader->createTraderOrderHistory(
            $lastTraderOrder,
            FinancingOrderHistory::AttachMpoDocument
        );

        $warrantDocument = $trader->getDocumentByTypeAndTransaction(
            $lastTraderOrder->reference,
            'Warrant Amendment Except Warrant No'
        );

        $trader->createTraderOrderHistory(
            $lastTraderOrder,
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument
        );

        $trader->attachDocumentToOrder(
            $lastTraderOrder,
            $warrantDocument,
            FinancingOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
            'base64'
        );

        $trader->createTraderOrderHistory(
            $lastTraderOrder,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument
        );

        app()->make(AskClientWakala::class)->handle(
            $financingOrder,
            Str::replace('{order_id}', $financingOrder->id, Config::get('frontend.client_wakala_url'))
        );

        $financingOrder->update([
            'status' => FinancingOrderStatus::WaitingClientWakala,
        ]);
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('financingOrder'.$this->financingOrder)];
    }
}
