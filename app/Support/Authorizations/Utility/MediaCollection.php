<?php

namespace App\Support\Authorizations\Utility;

use App\Enums\Area;
use App\Enums\FinancingOrderMediaCollection;

class MediaCollection
{
    public function getCollectionsByArea($area)
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
