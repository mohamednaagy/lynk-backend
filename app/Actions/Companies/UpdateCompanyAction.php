<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\UpdateCompany;
use App\Enums\WalletNotificationType;
use App\Models\Company;
use App\Models\TieredPricing;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class UpdateCompanyAction implements UpdateCompany
{
    public function handle(Company $company, array $data): Company
    {
        $company->update(
            Arr::only(
                $data,
                [
                    'name',
                    'notifications_email',
                    'unique_name',
                    'company_cr',
                    'contract_number',
                    'status',
                    'does_order_require_approval',
                    'webhook_secret_key',
                    'public_status_comment',
                    'internal_status_comment',
                    'driver',
                    'notify_admins_about_new_orders',
                    'trading_mode',
                    'require_initiate_trade_request',
                    'notify_borrowers_about_order_updates',
                    'force_unique_reference_number',
                    'preferred_market_type',

                ]
            )
        );

        if (isset($data['order_cost_tiers'])) {
            $isTieredBeforeUpdate = $company->isTiered();

            $this->updateCompanyPricingTiers($company, collect($data['order_cost_tiers']));

            $isTieredAfterUpdate = $company->isTiered();

            if ($isTieredBeforeUpdate != $isTieredAfterUpdate && $isTieredAfterUpdate) {
                $company->walletNotification()->where('type', WalletNotificationType::ORDER_COUNT)->delete();
            }
        }

        if (isset($data['preferred_commodity_types']) && ! empty($data['preferred_commodity_types'])) {
            $company->commodityTypes()->sync($data['preferred_commodity_types']);
        }

        return $company;
    }

    public function updateCompanyPricingTiers(Company $company, Collection $requestPricingTiers)
    {
        $requestPricingTiersIds = $requestPricingTiers->pluck('id');
        $deletedPricingTiersIds = $company->tieredPricing()->pluck('id')->diff($requestPricingTiersIds);

        foreach ($deletedPricingTiersIds as $tier_id) {
            TieredPricing::find($tier_id)->delete();
        }

        foreach ($requestPricingTiersIds as $tier_id) {
            $tier = $requestPricingTiers->where('id', $tier_id)->first();
            TieredPricing::find($tier_id)?->update($tier);
        }

        $newTiers = $requestPricingTiers->whereNull('id');
        foreach ($newTiers as $tier) {
            $company->tieredPricing()->create($tier);
        }
    }
}
