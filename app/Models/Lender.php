<?php

namespace App\Models;

use App\Enums\FinancingOrderTypeEnum;
use Watson\Rememberable\Rememberable;

class Lender extends Company
{
    use Rememberable;

    protected $rememberCacheTag = 'lenders';

    protected $rememberCachePrefix = 'lenders';

    protected $rememberFor = 60 * 60;

    public function getMorphClass()
    {
        return Company::class;
    }

    public function lenderDetail()
    {
        return $this->hasOne(CompanyLenderDetail::class, 'company_id', 'id');
    }

    public function getWallet(string $name, bool $lock = true): ?Wallet
    {
        return parent::getWallet($name, $lock);
    }

    /**
     * Check if the company is allowed to select preferred commodity types in their orders.
     *
     * This method checks if the company's lender has the 'allow_preferred_commodity_in_order' flag set to true.
     *
     * @return bool True if preferred commodity selection is allowed, false otherwise
     */
    public function isPreferredCommoditySelectionAllowed()
    {
        return $this->lenderDetail->allow_preferred_commodity_in_order;
    }

    public function allowedFinancingOrderTypes()
    {
        return $this->lenderDetail->allowed_financing_order_types;
    }

    public function isForceUniqueReferenceNumber()
    {
        return $this->lenderDetail->force_unique_reference_number;
    }

    public function getDefaultFinancingOrderTypeAttribute()
    {
        return $this->allowedFinancingOrderTypes()[0] ?? FinancingOrderTypeEnum::NormalLending;
    }

    public function isOrderApprovalRequired()
    {
        return $this->lenderDetail->does_order_require_approval;
    }
}
