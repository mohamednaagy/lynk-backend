<?php

namespace Tests\Support\FinancingOrders;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderHistory;
use App\Models\TraderOrder;

class InProgressOrder
{
    protected function __construct(protected FinancingOrder $financingOrder)
    {
    }

    public static function of(FinancingOrder|CommittedOrder $financingOrder)
    {
        if ($financingOrder->status->isNot(FinancingOrderStatus::InProgress)) {
            throw new \Exception('Order is not in progress');
        }

        return new static(
            $financingOrder instanceof CommittedOrder ?
                $financingOrder->model() : $financingOrder
        );
    }

    public function getFinancingOrder()
    {
        return $this->financingOrder;
    }

    public function createTraderOrder(
        string $driver = null,
        string $reference = '123456',
        int $status = TraderOrderStatus::InProgress,
        array $data = []
    ) {
        if ($status === TraderOrderStatus::InProgress && $this->doesInProgressTraderOrderExist()) {
            throw new \Exception('Active trader order exists');
        }

        $traderOrder = TraderOrder::withoutEvents(function () use ($reference, $status, $data) {
            return $this->financingOrder
                ->traderOrders()
                ->create(array_merge([
                    'provider' => $driver ?? config('trader.default'),
                    'reference' => $reference,
                    'status' => $status,
                ], $data));
        });

        TraderHistory::withoutEvents(function () use ($traderOrder) {
            $traderOrder->traderHistories()->create([
                'action' => FinancingOrderHistory::GetTtiId,
            ]);
        });

        return $traderOrder;
    }

    protected function doesInProgressTraderOrderExist()
    {
        return $this->financingOrder
            ->traderOrders()
            ->where('status', TraderOrderStatus::InProgress)
            ->exists();
    }
}
