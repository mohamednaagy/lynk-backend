<?php

namespace App\Support\Authorizations\Media\Utilities;

use App\Enums\Area;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\MediaCollections\TraderOrderMediaCollection;

class GetCollectionsByArea
{
    public function __invoke($area)
    {
        return match ($area) {
            Area::Lender, Area::SuperAdmin => array_merge(
                FinancingOrderMediaCollection::getValues(),
                TraderOrderMediaCollection::getValues()
            ),
            default => []
        };
    }
}
