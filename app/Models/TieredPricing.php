<?php

namespace App\Models;

use App\Enums\OrderFeeType;
use App\Exceptions\NoMatchOrderCostAndValueException;
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
     * @throws NoMatchOrderCostAndValueException
     */
    public static function getOrderCost(Company $company, Money $orderValue): Money
    {
        $pricing = self::getPricingTier($company, $orderValue);

        if (! $pricing) {
            throw new NoMatchOrderCostAndValueException();
        }

        if ($pricing->fee_type->is(OrderFeeType::Proration)) {
            return $pricing->order_cost_without_vat->multiply(
                $orderValue->divide($pricing->proration_amount)
            );
        }

        return $pricing->order_cost_without_vat;
    }

    public static function getPricingTier(Company $company, Money $orderValue): Builder|Model|null
    {
        return (new static)->newQuery()
            ->where('company_id', $company->getKey())
            ->where('order_value_start', '<=', $orderValue->getAmount())
            ->where(function (Builder $query) use ($orderValue) {
                $query->where('order_value_end', '>=', $orderValue->getAmount())
                    ->orWhereNull('order_value_end');
            })
            ->first();
    }
}
