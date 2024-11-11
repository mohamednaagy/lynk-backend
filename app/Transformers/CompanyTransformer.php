<?php

namespace App\Transformers;

use App\Models\Company;
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
        'contract_number',
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
        'notify_admins_about_new_orders',
        'notify_borrowers_about_order_updates',
        'force_unique_reference_number',
        'trading_mode',
        'require_initiate_trade_request',
        'contract_number',
        'preferred_market_type',
        'preferred_commodity_types',
        'auto_complete_murabaha_order',
    ];

    public function transform(Company $company): array
    {
        return [];
    }

    public function includeId(Company $company): Primitive
    {
        return $this->primitive($company->id);
    }

    public function includeName(Company $company): Primitive
    {
        return $this->primitive($company->name);
    }

    public function includeUniqueName(Company $company): Primitive
    {
        return $this->primitive($company->unique_name);
    }

    public function includeIsTiered(Company $company): Primitive
    {
        return $this->primitive($company->isTiered());
    }

    public function includeCompanyCr(Company $company): Primitive
    {
        return $this->primitive($company->company_cr);
    }

    public function includeContractNumber(Company $company): Primitive
    {
        return $this->primitive($company->contract_number);
    }

    public function includeNotificationsEmail(Company $company): Primitive
    {
        return $this->primitive($company->notifications_email);
    }

    public function includeStatus(Company $company): Primitive
    {
        return $this->primitive([
            'value' => $company->status->value,
            'description' => $company->status->description,
        ]);
    }

    public function includeOrdersCount(Company $company): Primitive
    {
        return $this->primitive($company->orders_count);
    }

    public function includeDoesOrderRequireApproval(Company $company): Primitive
    {
        return $this->primitive($company->does_order_require_approval);
    }

    public function includeTradingMode(Company $company): Primitive
    {
        return $this->primitive($company->trading_mode);
    }

    public function includeWebhookSecretKey(Company $company): Primitive
    {
        return $this->primitive($company->webhook_secret_key);
    }

    public function includeCreatedAt(Company $company): Primitive
    {
        return $this->primitive(optional($company->created_at)->format('Y-m-d'));
    }

    public function includePublicStatusComment(Company $company): Primitive
    {
        return $this->primitive($company->public_status_comment);
    }

    public function includeDriver(Company $company): Primitive
    {
        return $this->primitive($company->driver);
    }

    public function includeOrdersSumAmount(Company $company): Primitive
    {
        $amount = (new Money($company->orders_sum_amount, Money::getDefaultCurrency()))->convertAndFormatByDecimal();

        return $this->primitive(
            number_format($amount, 2)
        );
    }

    public function includeNotifyAdminsAboutNewOrders(Company $company)
    {
        return $this->primitive($company->notify_admins_about_new_orders);
    }

    public function includeForceUniqueReferenceNumber(Company $company)
    {
        return $this->primitive($company->force_unique_reference_number);
    }

    public function includeNotifyBorrowersAboutOrderUpdates(Company $company)
    {
        return $this->primitive($company->notify_borrowers_about_order_updates);
    }

    public function includeOrderCostTiers(Company $company): Collection
    {
        $orderCostTiers = $company->tieredPricing()
            ->orderBy('order_value_start')
            ->get();

        return $this->collection($orderCostTiers, new OrderCostTierTransformer);
    }

    public function includeRequireInitiateTradeRequest(Company $company)
    {
        return $this->primitive($company->require_initiate_trade_request);
    }

    public function includeAutoCompleteMurabahaOrder(Company $company)
    {
        return $this->primitive($company->auto_complete_murabaha_order);
    }

    public function includePreferredMarketType(Company $company): Primitive
    {
        $market_type = $company->preferred_market_type;
        if (is_null($market_type)) {
            return $this->primitive(null);
        }

        return $this->primitive([
            'value' => $market_type->value,
            'description' => $market_type->description,
        ]);

    }

    public function includePreferredCommodityTypes(Company $company): Primitive
    {
        $type = $company->commodityTypes()->first();

        if (is_null($type)) {
            return $this->primitive(null);
        }

        return $this->primitive([
            'id' => $type->id,
            'name' => $type->name,
        ]);

    }
}
