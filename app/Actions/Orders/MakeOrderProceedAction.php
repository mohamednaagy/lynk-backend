<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Http\UploadedFile;

class MakeOrderProceedAction implements MakeOrderProceed
{
    use TraderHelperTrait;

    protected ?UploadedFile $clientWakalaFile = null;

    /**
     * @param  TraderOrder  $traderOrder
     * @param  string  $case
     * @param  bool  $forceToProceed
     * @return mixed
     *
     * @throws OrderStatusDoesNotFollowSequenceException
     */
    public function handle(TraderOrder $traderOrder, string $case, $forceToProceed = false)
    {
        return match ($case) {
            FinancingOrderProceedCase::ClientWakalaAccepted => $this->handleClientWakalaAccepted($traderOrder, $forceToProceed),
            FinancingOrderProceedCase::ContractSigned => $this->handleContractSigned($traderOrder, $forceToProceed),
            default => []
        };
    }

    public function setClientWakala(UploadedFile $clientWakalaFile)
    {
        $this->clientWakalaFile = $clientWakalaFile;

        return $this;
    }

    protected function handleClientWakalaAccepted(TraderOrder $traderOrder, bool $forceToProceed)
    {
        $order = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($traderOrder->financing_order_id);

        if (
            $forceToProceed === false
            && ($order->is_verification_required
                || $order->status->cantMoveTo(FinancingOrderStatus::ClientWakalaCompleted)
            )
        ) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        $media = $this->proceedClientWakalaMedia($order, $traderOrder);

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ClientWakalaAccepted);

        $order->update([
            'status' => FinancingOrderStatus::ClientWakalaCompleted,
        ]);

        return [
            'wakala_file_url' => route('api.v1.media.download', ['media' => $media->uuid]),
        ];
    }

    protected function handleContractSigned(TraderOrder $traderOrder, bool $forceToProceed)
    {
        $order = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($traderOrder->financing_order_id);

        if ($forceToProceed === false && $order->status->cantMoveTo(FinancingOrderStatus::ContractSigned)) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        $order->update([
            'status' => FinancingOrderStatus::ContractSigned,
        ]);

        $traderOrder = $order->activeTraderOrder()->first();

        $traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::ContractSigned,
        ]);

        return [];
    }

    protected function proceedClientWakalaMedia(FinancingOrder $order, TraderOrder $traderOrder)
    {
        if (! $this->clientWakalaFile) {
            return app(AcceptClientWakala::class)->handle($order);
        }

        $order->update([
            'client_wakala_accepted_at' => now(),
        ]);

        return $traderOrder->addMedia($this->clientWakalaFile)
            ->toMediaCollection(TraderOrderMediaCollection::ClientWakala);
    }

    /**
     * @param  UploadedFile|null  $clientWakalaFile
     */
    public function __construct(?Illuminate\Http\UploadedFile $clientWakalaFile)
    {
        $this->clientWakalaFile = $clientWakalaFile;
    }
}
