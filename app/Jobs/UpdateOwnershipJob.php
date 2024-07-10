<?php

namespace App\Jobs;

use App\Actions\LocalMarket\UpdateOwnershipAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateOwnershipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inventory;
    protected $companyId;

    public function __construct($inventory, $companyId)
    {
        $this->inventory = $inventory;
        $this->companyId = $companyId;
    }

    public function handle(UpdateOwnershipAction $updateOwnershipAction)
    {
        $updateOwnershipAction->handle($this->inventory, $this->companyId);
    }
}
