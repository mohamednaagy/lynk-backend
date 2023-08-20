<?php

namespace App\Support\Collections;

use App\Enums\TransactionReason;
use App\Models\FinancingOrder;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/** @see \App\Models\FinancingOrder */
class FinancingOrderCollection extends Collection
{
    public function loadCost()
    {
        $ids = $this->pluck('id');

        $totalOrderCost = Transaction::select(['meta->financing_order_id as order_id',
            DB::raw('SUM(CASE WHEN reason = '.TransactionReason::OrderCreationFee.' THEN amount ELSE  -1 * amount END) as amount')])
            ->whereIn('meta->financing_order_id', $ids)
            ->whereIn('reason', [TransactionReason::OrderCreationFee, TransactionReason::RefundOrderCreationFee])
            ->groupBy('order_id')
            ->get();

        $totalOrderVat = Transaction::select(['meta->financing_order_id as order_id',
            DB::raw('SUM(CASE WHEN reason = '.TransactionReason::VatPercentageFee.' THEN amount ELSE  -1 * amount END) as amount')])
            ->whereIn('meta->financing_order_id', $ids)
            ->whereIn('reason', [TransactionReason::VatPercentageFee, TransactionReason::RefundVatPercentageFee])
            ->groupBy('order_id')
            ->get();

        return $this->transform(function (FinancingOrder $order) use ($totalOrderVat, $totalOrderCost) {
            return $order->setAttribute(
                'total_cost',
                $totalOrderCost->firstWhere('order_id', $order->id)?->amount->getAmount(),
            )->setAttribute(
                'total_vat',
                $totalOrderVat->firstWhere('order_id', $order->id)?->amount->getAmount(),
            );
        });
    }
}
