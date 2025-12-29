<?php

namespace App\Transformers;

use App\Enums\FinancingOrderTypeEnum;
use App\Models\Lender;
use Cknow\Money\Money;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class CompanyTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'name',
        'unique_name',
        'company_cr',
        'does_order_require_approval',
        'status',
        'orders_count',
        'created_at',
        'is_tiered',
        'order_cost_tiers',
        'webhook_secret_key',
        'public_status_comment',
        'driver',
        'notifications_email',
        'orders_sum_amount',
        'force_unique_reference_number',
        'trading_mode',
        'require_initiate_trade_request',
        'contract_number',
        'preferred_market_type',
        'preferred_commodity_types',
        'auto_complete_murabaha_order',
        'default_contract_sign_time_limit',
        'allow_preferred_commodity_in_order',
        'token_expire_in',
        'lender_order_allowed_commodity_types',
        'allowed_financing_order_types',

    ];

    public function transform(Lender $lender): array
    {
        return [];
    }

    public function includeId(Lender $lender): Primitive
    {
        return $this->primitive($lender->id);
    }

    public function includeName(Lender $lender): Primitive
    {
        return $this->primitive($lender->name);
    }

    public function includeUniqueName(Lender $lender): Primitive
    {
        return $this->primitive($lender->unique_name);
    }

    public function includeIsTiered(Lender $lender): Primitive
    {
        return $this->primitive($lender->isTiered());
    }

    public function includeCompanyCr(Lender $lender): Primitive
    {
        return $this->primitive($lender->lenderDetail?->company_cr);
    }

    public function includeContractNumber(Lender $lender): Primitive
    {
        return $this->primitive($lender->lenderDetail?->contract_number);
    }

    public function includeNotificationsEmail(Lender $lender): Primitive
    {
        return $this->primitive($lender->lenderDetail?->notifications_email);
    }

    public function includeStatus(Lender $lender): Primitive
    {
        return $this->primitive([
            'value' => $lender->status->value,
            'description' => $lender->status->description,
        ]);
    }

    public function includeOrdersCount(Lender $lender): Primitive
    {
        return $this->primitive($lender->orders_count);
    }

    public function includeDoesOrderRequireApproval(Lender $lender): Primitive
    {
        return $this->primitive($lender->lenderDetail?->does_order_require_approval);
    }

    public function includeTradingMode(Lender $lender): Primitive
    {
        return $this->primitive($lender->lenderDetail->trading_mode);
    }

    public function includeWebhookSecretKey(Lender $lender): Primitive
    {
        return $this->primitive($lender->lenderDetail->webhook_secret_key);
    }

    public function includeCreatedAt(Lender $lender): Primitive
    {
        return $this->primitive(optional($lender->created_at)->format('Y-m-d'));
    }

    public function includePublicStatusComment(Lender $lender): Primitive
    {
        return $this->primitive($lender->lenderDetail->public_status_comment);
    }

    public function includeDriver(Lender $lender): Primitive
    {
        return $this->primitive($lender->driver);
    }

    public function includeOrdersSumAmount(Lender $lender): Primitive
    {
        $amount = (new Money($lender->orders_sum_amount, Money::getDefaultCurrency()))->convertAndFormatByDecimal();

        return $this->primitive(
            number_format($amount, 2)
        );
    }

    public function includeForceUniqueReferenceNumber(Lender $lender)
    {
        return $this->primitive($lender->lenderDetail?->force_unique_reference_number);
    }

    public function includeAllowPreferredCommodityInOrder(Lender $lender)
    {
        return $this->primitive($lender->lenderDetail->allow_preferred_commodity_in_order);
    }

    public function includeOrderCostTiers(Lender $lender): Collection
    {
        $orderCostTiers = $lender->tieredPricing()
            ->orderBy('order_value_start')
            ->get();

        return $this->collection($orderCostTiers, new OrderCostTierTransformer);
    }

    public function includeRequireInitiateTradeRequest(Lender $lender)
    {
        return $this->primitive($lender->lenderDetail?->require_initiate_trade_request);
    }

    public function includeAutoCompleteMurabahaOrder(Lender $lender)
    {
        return $this->primitive($lender->lenderDetail->auto_complete_murabaha_order);
    }

    public function includePreferredMarketType(Lender $lender): Primitive
    {
        $market_type = $lender->lenderDetail?->preferred_market_type;
        if (is_null($market_type)) {
            return $this->primitive(null);
        }

        return $this->primitive([
            'value' => $market_type->value,
            'description' => $market_type->description,
        ]);

    }

    public function includePreferredCommodityTypes(Lender $lender): Primitive
    {
        $types = $lender->commodityTypes;

        if ($types->isEmpty()) {
            return $this->primitive([]);
        }

        return $this->primitive(
            $types->map(fn ($type) => [
                'id' => $type->id,
                'name' => $type->name,
                'provider' => $type->provider->value,
            ])->all()
        );

    }

    public function includeDefaultContractSignTimeLimit(Lender $lender): Primitive
    {
        return $this->primitive($lender->lenderDetail?->default_contract_sign_time_limit);
    }

    public function includeTokenExpireIn(Lender $lender): Primitive
    {
        return $this->primitive($lender->lenderDetail?->token_expire_in);
    }

    public function includeLenderOrderAllowedCommodityTypes(Lender $lender): Primitive
    {
        $types = $lender->lenderOrderAllowedCommodityTypes;

        if ($types->isEmpty()) {
            return $this->primitive([]);
        }

        return $this->primitive(
            $types->map(fn ($type) => [
                'id' => $type->id,
                'name' => $type->name,
                'provider' => $type->provider->value,
            ])->all()
        );
    }

    public function includeAllowedFinancingOrderTypes(Lender $lender): Primitive
    {
        return $this->primitive(collect($lender->allowedFinancingOrderTypes())->map(function ($value) {
            return [
                'id' => $value,
                'name' => FinancingOrderTypeEnum::getDescription($value),
            ];
        })->values());
    }
}
