<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CreateTraderOrder;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Exceptions\OrderAlreadyHasActiveTraderOrderException;
use App\Exceptions\OrderIsAlreadyCompletedException;
use App\Exceptions\OrderIsCancelledException;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateTraderOrderAction implements CreateTraderOrder
{
    use TraderHelperTrait;

    /**
     * @return mixed
     */
    public function handle($orderId, array $data): TraderOrder
    {

        $financingOrder = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($orderId);

        if ($financingOrder->status->is(FinancingOrderStatus::Completed)) {
            throw new OrderIsAlreadyCompletedException;
        }

        if ($financingOrder->status->is(FinancingOrderStatus::Cancelled)) {
            throw new OrderIsCancelledException;
        }

        $doesInProgressTraderOrderExists = $financingOrder
            ->traderOrders()
            ->where(function ($q) {
                $q->where('status', TraderOrderStatus::InProgress)->orWhere('status', TraderOrderStatus::Initiated)->orWhere('status', TraderOrderStatus::Hold);
            })
            ->exists();

        if ($doesInProgressTraderOrderExists) {
            throw new OrderAlreadyHasActiveTraderOrderException;
        }

        $commodityTypeId = isset($data['commodity_type_id']) ? $data['commodity_type_id'] : null;
        $data['creator_id'] = auth()?->user()?->id;
        $traderOrder = match ($data['mode']) {
            TraderOrderMode::Manual => $this->createTraderOrder($financingOrder, $data),
            TraderOrderMode::Automatic => Trader::driver($data['trader'], $data['version'])
                ->createTraderOrder($financingOrder, $commodityTypeId),
        };

        $this->updateOrderStatus($financingOrder, FinancingOrderStatus::InProgress);

        return $traderOrder;
    }

    private function createTraderOrder(FinancingOrder $financingOrder, $data): Model
    {
        $traderOrder = $financingOrder->traderOrders()->create([
            'provider' => Arr::get($data, 'trader'),
            'reference' => Arr::get($data, 'reference_number'),
            'version' => Arr::get($data, 'version'),
            'mode' => Arr::get($data, 'mode'),
            'creator_id' => Arr::get($data, 'creator_id'),
            'status' => Trader::driver($data['trader'], $data['version'])->getDefaultInitialTradeOrderStatus(),
        ]);

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);

        return $traderOrder;
    }
}
