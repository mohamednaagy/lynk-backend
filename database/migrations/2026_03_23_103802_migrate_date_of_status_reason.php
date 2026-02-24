<?php

declare(strict_types=1);

use App\Enums\FinancingOrderCancelReason;
use App\Enums\FinancingOrderRejectionReason;
use App\Enums\FinancingOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $rejectedStatus = FinancingOrderStatus::Rejected;
        $cancelledStatus = FinancingOrderStatus::Cancelled;
        $now = now();

        DB::table('financing_orders')
            ->select(['id', 'creator_id', 'status', 'status_reason'])
            ->whereIn('status', [$rejectedStatus, $cancelledStatus])
            ->orderBy('id')
            ->chunkById(1000, function ($orders) use ($rejectedStatus, $cancelledStatus, $now): void {
                $rejected = $orders->where('status', $rejectedStatus);
                $cancelled = $orders->where('status', $cancelledStatus);
                if ($rejected->isNotEmpty()) {
                    DB::table('financing_order_rejection_details')->insert(
                        $rejected->map(fn (object $order) => [
                            'financing_order_id' => $order->id,
                            'creator_id' => $order->creator_id,
                            'rejection_reason' => FinancingOrderRejectionReason::Rejected,
                            'comment' => $order->status_reason,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])->values()->all()
                    );
                }

                if ($cancelled->isNotEmpty()) {
                    DB::table('financing_order_cancel_details')->insert(
                        $cancelled->map(fn (object $order) => [
                            'financing_order_id' => $order->id,
                            'creator_id' => $order->creator_id,
                            'cancel_reason' => FinancingOrderCancelReason::Cancelled,
                            'comment' => $order->status_reason,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])->values()->all()
                    );
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('financing_order_rejection_details')->truncate();
        DB::table('financing_order_cancel_details')->truncate();
    }
};
