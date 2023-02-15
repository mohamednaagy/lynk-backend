<?php

namespace App\Actions\Orders\TraderOrders\MurabhaCompleteDocument;

use App\Actions\Contracts\Orders\TraderOrders\MurabhaCompleteDocument\HandleMurabhaCompleteDocument;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TraderHelperTrait;
use Exception;
use Illuminate\Http\Request;

class HandleMurabhaCompleteDocumentAction implements HandleMurabhaCompleteDocument
{
    use TraderHelperTrait;

    /**
     * @param  Request  $request
     * @param  FinancingOrder  $order
     * @param  TraderOrder  $traderOrder
     * @return void
     *
     * @throws Exception
     */
    public function handle(Request $request, FinancingOrder $order, TraderOrder $traderOrder): void
    {
        $isCurrentStepComplete = $traderOrder->checkOrderStepComplete(
            FinancingOrderStatus::MurabahaSaleCompleted
        );

        $shouldUpdateStatus = false === $isCurrentStepComplete;

        $trader = Trader::driver($traderOrder->provider);

        $this->createStepHistories(
            $request,
            $trader,
            $traderOrder,
            FinancingOrderStatus::MurabahaSaleCompleted
        );

        if ($shouldUpdateStatus) {
            $trader->updateOrderStatus($order, FinancingOrderStatus::MurabahaSaleCompleted);
        }
    }
}
