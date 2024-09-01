<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CancelTraderOrder;
use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Actions\Contracts\Orders\TraderOrders\InProgressTrader;
use App\Enums\TraderOrderCancelReason;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class LocalMarketWebhookAction implements LocalMarketWebhook
{
    public function handle(
        array $data,
    ): void {
        //        $traderOrder = TraderOrder::lockForUpdate()->where('reference', $data['external_order_no'])->firstOrFail();
        $traderOrder = TraderOrder::lockForUpdate()->latest()->firstOrFail();

        switch ($data['case']) {
            case 'CommoditiesPurchased':
                app(InProgressTrader::class)->handle($traderOrder, ['products' => $data['products']]);
                break;
            case 'FailedPurchase':
                Log::info('cancel trader order');
                //TODO nagy update cancel trader order
                //                app(CancelTraderOrder::class)->handle($traderOrder,user:User::first(), data:$data,cancelReason:TraderOrderCancelReason::Manual);
                break;

            case 'Cancelled':
                break;

            default:
                throw new \Exception('wrong format');
        }
    }
}
