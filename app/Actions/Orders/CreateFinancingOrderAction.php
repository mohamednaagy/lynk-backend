<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Enums\WalletType;
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
        $data = cast_phone_number_if_exist($data);

        $data['currency'] = $company->getWallet(WalletType::CompanyWallet)->currency;

        $data['amount'] = Money::parseByDecimal($data['amount'], $data['currency']);

        $data['selling_price'] = Money::parseByDecimal($data['selling_price'], $data['currency']);

        $data['contract_number'] = $company->contract_number;

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
            ])
        );
    }
}
