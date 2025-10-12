<?php

use App\Models\FinancingOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::beginTransaction();
        try {
            // Schema::table('financing_orders', function (Blueprint $table) {
            //     $table->decimal('cost_with_vat', 64, 0)->nullable()->after('amount');
            //     $table->decimal('cost_without_vat', 64, 0)->nullable()->after('cost_with_vat');
            // });

            $financingOrders = FinancingOrder::orderBy('id', 'desc')->get();
            foreach ($financingOrders as $financingOrder) {
                $transaction = $financingOrder->creationFeeTransactions()->latest()->first();
                if ($transaction) {
                    $meta = $transaction->meta;

                    if ($meta['is_vat_included']) {
                        $financingOrder->cost_with_vat = abs($transaction->amount->getAmount());
                        $financingOrder->cost_without_vat = $meta['order_cost']['amount'];
                    } else {
                        $financingOrder->cost_with_vat = abs($transaction->amount->getAmount());
                        $financingOrder->cost_without_vat = $meta['order_cost']['amount'] - ($meta['order_cost']['amount'] * 0.15);
                    }

                    $financingOrder->save();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->dropColumn('cost_with_vat');
            $table->dropColumn('cost_without_vat');
        });
    }
};
