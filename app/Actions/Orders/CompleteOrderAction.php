<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CompleteOrder;
use App\Actions\Contracts\Orders\GetOrderAndTraderOrderLockedForUpdate;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use Illuminate\Support\Arr;

class CompleteOrderAction implements CompleteOrder
{
    public function __construct(
        protected GetOrderAndTraderOrderLockedForUpdate $getOrderAndTraderOrderLockedForUpdate
    ) {
    }

    /**
     * @return mixed
     */
    public function handle($traderOrderId, array $data)
    {
        [$order, $traderOrder] = $this->getOrderAndTraderOrderLockedForUpdate->handle($traderOrderId);

        $traderOrder->ensureCanAccessStep(
            FinancingOrderStatus::MurabahaSaleCompleted
        );

        $order->addMedia(Arr::get($data, 'payment_proof'))
            ->toMediaCollection(FinancingOrderMediaCollection::PaymentProof);

        $traderOrder->update(['status' => TraderOrderStatus::Completed]);
        $order->update(['status' => FinancingOrderStatus::Completed]);

        return $order->getFirstMedia(FinancingOrderMediaCollection::PaymentProof);
    }
}
