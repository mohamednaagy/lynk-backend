<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Enums\WalletType;
use App\Models\CommodityType;
use App\Models\FinancingOrder;
use App\Models\Lender;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Model;

class CreateFinancingOrderAction implements CreateFinancingOrder
{
    public function __construct(
        private FinancingOrderTypeFactory $financingOrderTypeFactory
    ) {}

    /**
     * @return FinancingOrder|Model
     */
    public function handle(Lender $lender, array $data): FinancingOrder
    {
        $data = cast_phone_number_if_exist($data);

        $data['currency'] = $lender->getWallet(WalletType::CompanyWallet)->currency;

        $data['amount'] = Money::parseByDecimal($data['amount'], $data['currency']);

        $data['selling_price'] = Money::parseByDecimal($data['selling_price'], $data['currency']);

        $data['contract_number'] = $lender->lenderDetail->contract_number;

        $commodityTypeId = $data['commodity_type_id'] ?? null;

        $data['commodity_type_id'] = is_numeric($commodityTypeId)
            ? (int) $commodityTypeId
            : $this->findCommodityTypeIdByUniqueName($commodityTypeId, $lender);

        $strategy = $this->financingOrderTypeFactory->make($data['type']);

        return $strategy->create($lender, $data);

    }

    private function findCommodityTypeIdByUniqueName(?string $uniqueName, Lender $lender): ?int
    {
        $allowCommoditySelection = $lender->lenderDetail?->allow_preferred_commodity_in_order ?? false;

        // If no unique name provided, return null
        if (! $allowCommoditySelection || is_null($uniqueName) || $uniqueName === '') {
            return null;
        }

        return CommodityType::where('unique_name', $uniqueName)->value('id');
    }
}
