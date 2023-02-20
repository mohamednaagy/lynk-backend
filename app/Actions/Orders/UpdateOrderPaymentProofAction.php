<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\UpdateOrderPaymentProof;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\FinancingOrder;
use Illuminate\Support\Arr;

class UpdateOrderPaymentProofAction implements UpdateOrderPaymentProof
{
    /**
     * @return mixed
     */
    public function handle($financingOrderId, array $data)
    {
        $order = FinancingOrder::lockForUpdate()
            ->findOrFail($financingOrderId);

        if (! $order->status->is(FinancingOrderStatus::Completed)) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        $order->addMedia(Arr::get($data, 'payment_proof'))
            ->toMediaCollection(FinancingOrderMediaCollection::PaymentProof);

        return $order->getFirstMedia(FinancingOrderMediaCollection::PaymentProof);
    }
}
