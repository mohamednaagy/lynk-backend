<?php

namespace App\Models;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Enums\OrderFeeType;
use App\Exceptions\NoMatchOrderCostAndValueException;
use App\Support\Money\Casts\MoneyStringCast;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TieredPricing extends Model
{
    protected $table = 'tiered_pricing';

    protected $fillable = [
        'order_value_start',
        'order_value_end',
        'fee_type',
        'order_cost_without_vat',
        'vat_amount',
        'proration_amount',
    ];

    protected $casts = [
        'order_value_start' => MoneyStringCast::class,
        'order_value_end' => MoneyStringCast::class,
        'fee_type' => OrderFeeType::class,
        'order_cost_without_vat' => MoneyStringCast::class,
        'vat_amount' => MoneyStringCast::class,
        'proration_amount' => MoneyStringCast::class,
    ];

    /**
     * @throws NoMatchOrderCostAndValueException
     */
    public static function getOrderCostWithoutVat(Lender $lender, Money $orderValue): Money
    {
        $pricing = self::getPricingTier($lender, $orderValue);

        if (! $pricing) {
            throw new NoMatchOrderCostAndValueException;
        }

        if ($pricing->fee_type->is(OrderFeeType::Proration)) {
            return $pricing->order_cost_without_vat->multiply(
                $orderValue->divide($pricing->proration_amount->formatByDecimal())->formatByDecimal()
            );
        }

        return $pricing->order_cost_without_vat;
    }

    /**
     * @throws NoMatchOrderCostAndValueException
     */
    public static function getOrderCostWithVat(Lender $lender, Money $orderValue): Money
    {
        $orderCostWithoutVat = self::getOrderCostWithoutVat($lender, $orderValue);

        [$vatAmount] = app(CalculateVatAmount::class)
            ->setAmount($orderCostWithoutVat)
            ->setIsVatIncludedInAmount(false)
            ->handle();

        return $orderCostWithoutVat->add($vatAmount);
    }

    public static function getOrderCostIfStandard(Lender $lender): ?array
    {
        if ($lender->isTiered()) {
            return null;
        }

        $tier = (new static)->newQuery()
            ->where('company_id', $lender->getKey())
            ->first();

        [$vatAmount, $vatRate] = app(CalculateVatAmount::class)
            ->setAmount($tier->order_cost_without_vat)
            ->setIsVatIncludedInAmount(false)
            ->handle();

        return [
            'costWithoutVat' => $tier->order_cost_without_vat,
            'costWithVat' => $tier->order_cost_without_vat
                ->add($vatAmount)
                ->convertToDisplayableCurrency()
                ->convertToCurrency($tier->order_cost_without_vat->getCurrency()),
            'vatRate' => $vatRate,
        ];
    }

    public static function getPricingTier(Lender $lender, Money $orderValue): Builder|Model|null
    {
        return (new static)->newQuery()
            ->where('company_id', $lender->getKey())
            ->where('order_value_start', '<=', $orderValue->getAmount())
            ->where(function (Builder $query) use ($orderValue) {
                $query->where('order_value_end', '>=', $orderValue->getAmount())
                    ->orWhereNull('order_value_end');
            })->first();
    }

    /**
     * Retrieve the VAT amount for a given company's tiered pricing configuration.
     *
     * This method resolves the applicable tiered pricing record for the given
     * company and order value, then determines the VAT amount based on the
     * pricing type:
     *
     *   If the resolved `tiered_pricing` entry has a type of `proration`,
     *   the VAT amount is calculated dynamically using the provided
     *   `orderCostWithoutVat` and the configured VAT rate.
     *
     *   For fixed type, the method simply returns the VAT amount
     *   stored in the database (`vat_amount` column) without recalculation.
     *
     * @param  \App\Models\Lender  $lender  The company whose pricing tiers apply.
     * @param  \App\ValueObjects\Money  $orderValue  The total order value used to locate the tier.
     * @param  \App\ValueObjects\Money  $orderCostWithoutVat  The order cost excluding VAT.
     * @return \App\ValueObjects\Money The VAT amount as a Money value object.
     *
     * @throws \App\Exceptions\NoMatchOrderCostAndValueException
     *                                                           If no tiered pricing record matches the given order value.
     */
    public static function getVatAmount(Lender $lender, Money $orderValue, Money $orderCostWithoutVat): Money
    {
        $pricing = self::getPricingTier($lender, $orderValue);

        if (! $pricing) {
            throw new NoMatchOrderCostAndValueException;
        }

        if ($pricing->fee_type->is(OrderFeeType::Proration)) {
            [$vatAmount] = app(CalculateVatAmount::class)
                ->setAmount($orderCostWithoutVat)
                ->setIsVatIncludedInAmount(false)
                ->handle();

            return $vatAmount;
        }

        return $pricing->vat_amount;
    }

    public function lender(): BelongsTo
    {
        return $this->belongsTo(Lender::class, 'company_id');
    }
}
