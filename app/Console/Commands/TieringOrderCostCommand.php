<?php

namespace App\Console\Commands;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Enums\OrderFeeType;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class TieringOrderCostCommand extends Command
{
    protected $signature = 'tiering:order-cost';

    protected $description = 'Command description';

    public function handle(GetProjectSettings $getProjectSettings): void
    {
        $vatRate = $getProjectSettings->handle()->getVatRate();
        Company::doesntHave('tieredPricing')
            ->chunk(20, function (Collection $companies) use ($vatRate) {
                $companies->each(function (Company $company) use ($vatRate) {
                    $orderCostWithoutVat = $company->order_cost->getAmount();
                    $orderCostWithVat = $company->order_cost->multiply($vatRate);

                    $company->tieredPricing()->create([
                        'order_value_start' => 0,
                        'order_value_end' => null,
                        'fee_type' => OrderFeeType::Fixed,
                        'order_cost_without_vat' => $orderCostWithoutVat,
                        'order_cost_with_vat' => $orderCostWithVat,
                    ]);
                });
            });
    }
}
