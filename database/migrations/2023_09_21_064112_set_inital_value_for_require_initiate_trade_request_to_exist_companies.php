<?php

use App\Enums\TraderOrderMode;
use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Company::query()
            ->chunkById(20, function (Collection $companies) {
                $companies->each(function (Company $company) {
                    $company->update([
                        'require_initiate_trade_request' => $company->trading_mode->is(TraderOrderMode::Manual) || ! $company->does_order_require_approval,
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
