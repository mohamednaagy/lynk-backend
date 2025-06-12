<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Enums\WalletType;
use App\Models\CommodityType;
use App\Models\Company;
use App\Models\FinancingOrder;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateFinancingOrderAction implements CreateFinancingOrder
{
    /**
     * @return FinancingOrder|Model
     */
    public function handle(Company $company, array $data): FinancingOrder
    {
        $lender = $company->lender;

        $data = cast_phone_number_if_exist($data);

        $data['currency'] = $company->getWallet(WalletType::CompanyWallet)->currency;

        $data['amount'] = Money::parseByDecimal($data['amount'], $data['currency']);

        $data['selling_price'] = Money::parseByDecimal($data['selling_price'], $data['currency']);

        $data['contract_number'] = $lender->lenderDetail->contract_number;

        $data['commodity_type_id'] = $this->getCommodityTypeId($data['commodity_type_id'] ?? null, $company);

        return $company->orders()->create(
            Arr::only($data, [
                'customer_name',
                'reference_number',
                'national_id',
                'contract_number',
                'phone_number',
                'amount',
                'selling_price',
                'currency',
                'status',
                'creator_id',
                'creator_type',
                'approved_at',
                'is_verification_required',
                'commodity_type_id',
            ])
        );
    }

    private function getCommodityTypeId(?string $uniqueName, Company $company): ?int
    {
        $allowCommoditySelection = $company?->lender?->lenderDetail?->allow_preferred_commodity_in_order ?? false;

        // If no unique name provided, return null
        if (! $allowCommoditySelection || is_null($uniqueName) || $uniqueName === '') {
            return null;
        }

        // Validation already ensured this commodity type exists and is valid
        $commodityType = CommodityType::where('unique_name', $uniqueName)->first();

        return $commodityType?->id;
    }
}
