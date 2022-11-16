<?php

namespace App\Support\Authorizations\Media\Utilities;

use App\Enums\Area;
use App\Enums\FinancingOrderMediaCollection;

class GetCollectionsByArea
{
    public function __invoke($area)
    {
        return match ($area) {
            Area::Lender => array_merge(
                FinancingOrderMediaCollection::getValues()
            ),
            Area::SuperAdmin => array_merge(
                FinancingOrderMediaCollection::getValues()
            ),
            default => []
        };
    }
}
