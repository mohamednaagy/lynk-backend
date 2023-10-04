<?php

use App\Enums\OrderFeeType;
use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Company::doesntHave('tieredPricing')
            ->chunkById(20, function (Collection $companies) {
                $companies->each(function (Company $company) {

                    $orderCostWithoutVat = money($company->order_cost, $company->order_cost_currency);

                    $company->tieredPricing()->create([
                        'order_value_start' => 0,
                        'order_value_end' => null,
                        'fee_type' => OrderFeeType::Fixed,
                        'order_cost_without_vat' => $orderCostWithoutVat,
                    ]);
                });
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
