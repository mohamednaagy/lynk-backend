<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $transactions = \App\Models\Transaction::where(function ($query) {
            $query->where('reason', 1)
                ->orWhere('reason', 5);
        })
            ->whereRaw('NOT JSON_CONTAINS_PATH(meta, \'one\', "$.trader_order_id")')
            ->get();

        foreach ($transactions as $transaction) {
            if (isset($transaction->meta['financing_order_id'])) {
                $order_query = \App\Models\TraderOrder::where('financing_order_id', $transaction->meta['financing_order_id']);
                $order_counts = $order_query->count();
                $orders = $order_query->get();
                $get_order = $order_query->first();
                if ($order_counts > 1) {
                    Log::channel('custom')->info('------------- Transaction Have more than Trade Order ----------------');
                    Log::channel('custom')->info("Transaction id => $transaction->id");
                    Log::channel('custom')->info('Financing Order id => '.$transaction->meta['financing_order_id']);
                    Log::channel('custom')->info('Trade Orders Id => '.json_encode($orders->pluck('id')->toArray()));
                    Log::channel('custom')->info('------------------------------------------------------------');
                }
                if (is_null($get_order)) {
                    Log::channel('custom')->info('------------- Financing Order Id Doesnt Exist At Trade Order ----------------');
                    Log::channel('custom')->info("Transaction id => $transaction->id");
                    Log::channel('custom')->info('Financing Order id => '.$transaction->meta['financing_order_id']);
                    Log::channel('custom')->info('------------------------------------------------------------');
                } else {
                    $new_meta = $transaction->meta;
                    $new_meta['trader_order_id'] = $get_order->id;
                    $transaction->update([
                        'meta' => $new_meta,
                    ]);
                }

            } else {
                Log::channel('custom')->info('------------- Financing Order Attribute In Meta Json Doesnt Exist ----------------');
                Log::channel('custom')->info("Transaction id => $transaction->id");
                Log::channel('custom')->info('------------------------------------------------------------');
            }
            //                dd($transaction->meta['financing_order_id']);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
