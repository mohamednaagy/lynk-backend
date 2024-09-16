<?php

namespace App\Observers;

use App\Actions\Commodities\CommodityType\UpdateCommodityTypeStatusAction;
use App\Models\CommodityType;
class CommodityTypeObserver
{
    public $afterCommit = true;

    protected $UpdateCommodityTypeStatusAction;

    public function __construct(UpdateCommodityTypeStatusAction $UpdateCommodityTypeStatusAction)
    {
        $this->UpdateCommodityTypeStatusAction = $UpdateCommodityTypeStatusAction;
    }

    /**
     * Handle the CommodityItem "updated" event.
     *
     * @return void
     */
    public function updated(CommodityType $type)
    {
        if ($type->wasChanged('status')) {
            $this->UpdateCommodityTypeStatusAction->handle($type->id, $type->status->value);
        }
    }
}
