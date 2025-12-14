<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CompleteOrder;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\FinancingOrder;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class CompleteOrderAction implements CompleteOrder
{
    use TraderHelperTrait;

    /**
     * @return mixed
     */
    public function handle($orderId, array $data)
    {
        $financingOrder = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($orderId);

        if (! $financingOrder->canBeCompleted()) {
            Log::error('financing_order_id '.$financingOrder->id.' cant be completed at CompleteOrderAction', [
                'financingOrderId' => $financingOrder->id,
            ]);
            throw new OrderStatusDoesNotFollowSequenceException(
                [
                    'financingOrderId' => $financingOrder->id,
                    'traderOrderId' => null,
                ]
            );
        }

        if ($paymentProofMedia = Arr::get($data, 'payment_proof')) {
            $financingOrder->addMedia($paymentProofMedia)
                ->toMediaCollection(FinancingOrderMediaCollection::PaymentProofFromLenderToCustomer);
        }

        $this->updateOrderStatus($financingOrder, FinancingOrderStatus::Completed);
    }
}
