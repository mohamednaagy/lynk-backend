<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\FinancingOrderStatus;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;

class MakeOrderProceedAction implements MakeOrderProceed
{
    /**
     * @param  mixed  $order
     * @return mixed
     */
    public function handle(TraderOrder $traderOrder, string $case, $forceToProceed = false)
    {
        return match ($case) {
            FinancingOrderProceedCase::ClientWakalaAccepted => $this->handleClientWakalaAccepted($traderOrder, $forceToProceed),
            FinancingOrderProceedCase::ContractSigned => $this->handleContractSigned($traderOrder, $forceToProceed),
            default => []
        };
    }

    protected function handleClientWakalaAccepted(TraderOrder $traderOrder, bool $forceToProceed)
    {
        $order = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($traderOrder->financing_order_id);

        if (
            $forceToProceed === false
            && (
                $order->is_verification_required
                || $order->status->cantMoveTo(FinancingOrderStatus::ClientWakalaCompleted)
            )
        ) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        $media = app(AcceptClientWakala::class)->handle($order);

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
}
