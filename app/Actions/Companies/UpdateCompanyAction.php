<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\UpdateCompany;
use App\Enums\WalletNotificationType;
use App\Models\Lender;
use App\Models\TieredPricing;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class UpdateCompanyAction implements UpdateCompany
{
    public function handle(Lender $lender, array $data): Lender
    {
        $lender->update(
            Arr::only(
                $data,
                [
                    'name',
                    'unique_name',
                    'status',
                ]
            )
        );

        if (isset($data['order_cost_tiers'])) {
            $isTieredBeforeUpdate = $lender->isTiered();

            $this->updateCompanyPricingTiers($lender, collect($data['order_cost_tiers']));

            $isTieredAfterUpdate = $lender->isTiered();

            if ($isTieredBeforeUpdate != $isTieredAfterUpdate && $isTieredAfterUpdate) {
                $lender->walletNotification()->where('type', WalletNotificationType::ORDER_COUNT)->delete();
            }
        }

        if (isset($data['preferred_commodity_types'])) {
            $lender->commodityTypes()->sync($data['preferred_commodity_types']);
        }

        if (isset($data['lender_order_allowed_commodity_types']) && $this->isAllowedToUpdatePreferredCommodityInOrder($lender, $data['allow_preferred_commodity_in_order'])) {
            $lender->lenderOrderAllowedCommodityTypes()->sync($data['lender_order_allowed_commodity_types']);
        }

        $lender->lenderDetail()->updateOrCreate(
            ['company_id' => $lender->id],
            Arr::only($data, [
                'default_contract_sign_time_limit',
                'force_unique_reference_number',
                'notifications_email',
                'company_cr',
                'contract_number',
                'preferred_market_type',
                'does_order_require_approval',
                'notify_admins_about_new_orders',
                'allow_preferred_commodity_in_order',
                'require_initiate_trade_request',
                'trading_mode',
                'internal_status_comment',
                'public_status_comment',
                'auto_complete_murabaha_order',
                'webhook_secret_key',
                'allowed_financing_products',
            ])
        );

        return $lender;
    }

    public function updateCompanyPricingTiers(Lender $lender, Collection $requestPricingTiers)
    {
        $requestPricingTiersIds = $requestPricingTiers->pluck('id');
        $deletedPricingTiersIds = $lender->tieredPricing()->pluck('id')->diff($requestPricingTiersIds);

        foreach ($deletedPricingTiersIds as $tier_id) {
            TieredPricing::find($tier_id)->delete();
        }

        foreach ($requestPricingTiersIds as $tier_id) {
            $tier = $requestPricingTiers->where('id', $tier_id)->first();
            TieredPricing::find($tier_id)?->update($tier);
        }

        $newTiers = $requestPricingTiers->whereNull('id');
        foreach ($newTiers as $tier) {
            $lender->tieredPricing()->create($tier);
        }
    }

    private function isAllowedToUpdatePreferredCommodityInOrder(Lender $lender, bool $allowPreferredCommodityInOrder)
    {
        return $lender->isPreferredCommoditySelectionAllowed() && $allowPreferredCommodityInOrder;
    }
}
