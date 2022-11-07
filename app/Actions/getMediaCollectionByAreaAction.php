<?php

namespace App\Actions;

use App\Actions\Contracts\getMediaCollectionByArea;
use App\Enums\Area;
use App\Enums\FinancingOrderMediaCollection;

class getMediaCollectionByAreaAction implements getMediaCollectionByArea
{
    /**
     * @param  mixed  $area
     * @return mixed
     */
    public function handle($area)
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
