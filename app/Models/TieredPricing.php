<?php

namespace App\Models;

use App\Enums\OrderFeeType;
use App\Exceptions\OrderCostException;
use App\Support\Money\Casts\MoneyStringCast;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TieredPricing extends Model
{
    protected $table = 'tiered_pricing';

    protected $fillable = [
        'order_value_start',
        'order_value_end',
        'fee_type',
        'order_cost_without_vat',
        'order_cost_with_vat',
        'proration_amount',
    ];

    protected $casts = [
        'order_value_start' => MoneyStringCast::class,
        'order_value_end' => MoneyStringCast::class,
        'fee_type' => OrderFeeType::class,
        'order_cost_without_vat' => MoneyStringCast::class,
        'order_cost_with_vat' => MoneyStringCast::class,
        'proration_amount' => MoneyStringCast::class,
    ];

    /**
     * @throws OrderCostException
     */
    public static function getOrderPrice(Company $company, Money $orderValue): Money
    {
        $pricing = (new static)->newQuery()
            ->where('company_id', $company->id)
            ->where('order_value_start', '<=', $orderValue->getAmount())
            ->where(function (Builder $query) use ($orderValue) {
                $query->where('order_value_end', '>=', $orderValue->getAmount())
                    ->orWhereNull('order_value_end');
            })
            ->first();

        if (! $pricing) {
            throw new OrderCostException();
        }

        if ($pricing->fee_type->is(OrderFeeType::Proration)) {
            return $pricing->order_cost_without_vat->multiply(
                $orderValue->divide($pricing->proration_amount)
            );
        }

        return $pricing->order_cost_without_vat;
    }
}
