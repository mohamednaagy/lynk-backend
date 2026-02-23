<?php

declare(strict_types=1);

use App\Enums\FinancingOrderCancelReason;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\FinancingOrderCancelDetail;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        FinancingOrder::whereNotNull('status_reason')->get()->each(function (FinancingOrder $financingOrder) {
            $financingOrder->cancelDetail()->create([
                'creator_id' => $financingOrder->creator_id,
                'cancel_reason' => $financingOrder->status->value === FinancingOrderStatus::Cancelled ? FinancingOrderCancelReason::Cancelled : FinancingOrderCancelReason::Rejected,
                'comment' => $financingOrder->status_reason,
            ]);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        FinancingOrderCancelDetail::truncate();
    }
};
