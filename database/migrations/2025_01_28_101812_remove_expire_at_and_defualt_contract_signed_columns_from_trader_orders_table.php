<?php

use App\Models\TraderOrder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::beginTransaction();
        try {
            $traderOrders = TraderOrder::whereNotNull('expire_at')
                ->whereNotNull('default_contract_sign_time_limit')
                ->get();

            foreach ($traderOrders as $order) {
                if (! $order->timeLimits()->exists()) {
                    throw new ModelNotFoundException("Missing TraderLimit for TraderOrder ID: {$order->id}");
                }

                $order->update([
                    'expire_at' => null,
                    'default_contract_sign_time_limit' => null,
                ]);
            }

            $traderOrdersCount = TraderOrder::whereNotNull('expire_at')
                ->whereNotNull('default_contract_sign_time_limit')
                ->count();

            if ($traderOrdersCount == 0) {
                Schema::table('trader_orders', function (Blueprint $table) {
                    $table->dropColumn(['expire_at', 'default_contract_sign_time_limit']);
                });
            }

            DB::commit();
        } catch (ModelNotFoundException $e) {
            DB::Rollback();
            Log::error('TraderLimit check failed: '.$e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            DB::Rollback();
            Log::error('Unexpected error: '.$e->getMessage());
        }

        // Schema::table('trader_orders', function (Blueprint $table) {
        //     $table->dropColumn(['expire_at', 'default_contract_sign_time_limit']);
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->dateTime('expire_at')->nullable()->after('default_contract_sign_time_limit');
            $table->integer('default_contract_sign_time_limit')->nullable()->comment('Default measurement unit (minutes)');
        });
    }
};
