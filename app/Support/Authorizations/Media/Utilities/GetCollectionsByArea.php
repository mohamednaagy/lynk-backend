<?php

namespace App\Support\Authorizations\Media\Utilities;

use App\Enums\Area;
use App\Enums\FinancingOrderMediaCollection;

class GetCollectionsByArea
{
    public function __invoke($area)
    {
        return match ($area) {
            Area::Lender => [
                FinancingOrderMediaCollection::BankWakala,
                FinancingOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
                FinancingOrderMediaCollection::Contract,
            ],
            Area::SuperAdmin => [
                FinancingOrderMediaCollection::Contract,
            ],
            default => []
        };
    }
}
