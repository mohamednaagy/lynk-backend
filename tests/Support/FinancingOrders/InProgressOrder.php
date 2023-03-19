<?php

namespace Tests\Support\FinancingOrders;

use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;

class InProgressOrder
{
    protected function __construct(protected FinancingOrder $financingOrder)
    {
    }

    public static function startFrom(FinancingOrder $financingOrder)
    {
        return new static($financingOrder);
    }

    public function getFinancingOrder()
    {
        return $this->financingOrder;
    }

    protected function createTraderOrder(
        string $driver,
        string $reference = '123456',
        int $status = TraderOrderStatus::InProgress
    ) {
        if ($status === TraderOrderStatus::InProgress && $this->doesInProgressTraderOrderExist()) {
            throw new \Exception('Active trader order exists');
        }

        return $this->financingOrder
            ->traderOrders()
            ->create([
                'driver' => $driver ?? config('trader.default'),
                'reference' => $reference,
                'status' => $status,
            ]);
    }

    protected function doesInProgressTraderOrderExist()
    {
        return $this->financingOrder
            ->traderOrders()
            ->where('status', TraderOrderStatus::InProgress)
            ->exists();
    }
}
