<?php

namespace App\Support\Collections;

use App\Enums\TransactionReason;
use App\Models\FinancingOrder;
use App\Models\Transaction;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/** @see \App\Models\FinancingOrder */
class FinancingOrderCollection extends Collection
{
    public function loadCost()
    {
        $ids = $this->pluck('id');

        $totalOrderCost = Transaction::select([
            'meta->financing_order_id as order_id',
            DB::raw('-1 * SUM(amount) as amount'),
            DB::raw('SUM((CASE WHEN reason = '.TransactionReason::OrderCreationFee.' THEN 1 ELSE -1 END) * JSON_EXTRACT(meta, "$.vat_amount.amount")) as vat'),
        ])
            ->whereIn('meta->financing_order_id', $ids)
            ->whereIn('reason', [TransactionReason::OrderCreationFee, TransactionReason::RefundOrderCreationFee])
            ->groupBy('order_id')
            ->get();

        $totalOrderVat = Transaction::select([
            'meta->financing_order_id as order_id',
            DB::raw('-1 * SUM(amount) as amount'),
        ])
            ->whereIn('meta->financing_order_id', $ids)
            ->whereIn('reason', [TransactionReason::VatPercentageFee, TransactionReason::RefundVatPercentageFee])
            ->groupBy('order_id')
            ->get();

        return $this->transform(function (FinancingOrder $order) use ($totalOrderVat, $totalOrderCost) {
            $totalOrderCost = $totalOrderCost->firstWhere('order_id', $order->id);

            if ($totalOrderCost && $totalOrderCost->vat) {
                $totalOrderCostAmount = $totalOrderCost?->amount;
                $totalOrderCostWithoutVatAmount = $totalOrderCostAmount->getAmount()
                    ? $totalOrderCostAmount->subtract(Money::parse((string) intval($totalOrderCost->vat)))
                    : new Money(0);
            } else {
                $totalOrderCostAmount = $totalOrderCost?->amount->add(
                    $totalOrderVat->firstWhere('order_id', $order->id)?->amount ?? new Money(0)
                );

                $totalOrderCostWithoutVatAmount = $totalOrderCost?->amount ?? new Money(0);
            }

            return $order->setAttribute(
                'cost_with_vat',
                $totalOrderCostAmount,
            )->setAttribute(
                'cost_without_vat',
                $totalOrderCostWithoutVatAmount,
            );
        });
    }
}
