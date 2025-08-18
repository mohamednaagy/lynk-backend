<?php

namespace App\Support\DocumentEngine\Traits;

use App\Models\TraderOrder;

trait HasTraderOrder
{
    private $traderOrder = null;

    public function isTraderOrderInContext(): bool
    {
        return isset($this->context['trader_order']) && $this->context['trader_order'] !== null;
    }

    public function getTraderOrder()
    {
        if (! $this->isTraderOrderInContext()) {
            throw new \Exception('Trader order not found in context');
        }

        if ($this->traderOrder === null) {
            $this->traderOrder = TraderOrder::where('order_id', $this->getTraderOrderFromContext())->first();
        }

        return $this->traderOrder;
    }

    private function getTraderOrderFromContext()
    {
        return $this->context['trader_order'];
    }
}
