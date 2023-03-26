<?php

use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
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
        FinancingOrder::query()
            ->whereNotIn('status', FinancingOrderStatus::getValues())
            ->update(['status' => FinancingOrderStatus::InProgress]);
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
