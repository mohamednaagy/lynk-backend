<?php

namespace App\Transformers;

use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use Illuminate\Support\Arr;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class TraderOrderTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    protected array $availableIncludes = [
        'id',
        'financing_order_id',
        'reference',
        'provider',
        'data',
        'status',
        'cancelable',
        'history',
    ];

    public function transform(TraderOrder $traderOrder)
    {
        return [];
    }

    public function includeId(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->id);
    }

    public function includeFinancingOrderId(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->financing_order_id);
    }

    public function includeReference(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->reference);
    }

    public function includeProvider(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->provider);
    }

    public function includeCancelable(TraderOrder $traderOrder): Primitive
    {
        $traderHistoryActions = $traderOrder->traderHistories->pluck('action');

        return $this->primitive(! Arr::hasAny(FinancingOrderHistory::$notCancelableActions, $traderHistoryActions));
    }

    public function includeHistory(TraderOrder $traderOrder): Collection
    {
        return $this->collection(collect([
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
            FinancingOrderHistory::ContractSigned,
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
            'client_wakala',
            FinancingOrderHistory::IssueMurabahaOffer,
            FinancingOrderHistory::MurabahaSaleCompleted,
        ]), new TraderHistoryTransformer($traderOrder->order, $traderOrder->traderHistories ?? collect()));
    }

    public function includeStatus(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive([
            'description' => TraderOrderStatus::fromValue($traderOrder->status)->description,
            'value' => TraderOrderStatus::fromValue($traderOrder->status)->value,
        ]);
    }
}
