<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CompleteOrder;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\FinancingOrder;
use Illuminate\Support\Arr;

class CompleteOrderAction implements CompleteOrder
{
    /**
     * @return mixed
     */
    public function handle($orderId, array $data)
    {
        $financingOrder = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($orderId);

        if ($financingOrder->cantBeCompleted()) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        if ($paymentProofMedia = Arr::get($data, 'payment_proof')) {
            $financingOrder->addMedia($paymentProofMedia)
                ->toMediaCollection(FinancingOrderMediaCollection::PaymentProofFromLenderToCustomer);
        }

        $financingOrder->update(['status' => FinancingOrderStatus::Completed]);
    }
}
