<?php

use App\Models\FinancingOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->decimal('cost_with_vat', 64, 0)->nullable()->after('amount');
            $table->decimal('cost_without_vat', 64, 0)->nullable()->after('cost_with_vat');
        });

        FinancingOrder::orderBy('id')
            ->chunkById(500, function ($orders) {
                foreach ($orders as $financingOrder) {
                    $transaction = $financingOrder->creationFeeTransactions()->latest()->first();

                    if (! $transaction) {
                        continue;
                    }

                    $meta = $transaction->meta;
                    $financingOrder->cost_with_vat = abs($transaction->amount->getAmount());

                    try {
                        if ($meta['is_vat_included']) {
                            $financingOrder->cost_without_vat = $meta['order_cost']['amount'];
                        } else {
                            $financingOrder->cost_without_vat = $meta['order_cost']['amount'] - ($meta['order_cost']['amount'] * 0.15);
                        }

                        $financingOrder->saveQuietly();
                    } catch (\Throwable $e) {
                        logger()->error('Failed to update FinancingOrder ID '.$financingOrder->id, [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
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
