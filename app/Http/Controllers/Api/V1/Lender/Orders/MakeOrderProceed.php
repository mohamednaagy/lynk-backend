<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Actions\Contracts\Orders\ApproveOrder as ApproveOrderInterface;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\MakeOrderProceedRequest;
use App\Jobs\UpdateFinancialOrderStatus;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MakeOrderProceed extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @param  ApproveOrderInterface  $approveOrder
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function __invoke(
        MakeOrderProceedRequest $request,
        AcceptClientWakala $acceptClientWakala,
        $order
    ) {
        return DB::transaction(function () use ($request, $acceptClientWakala, $order) {
            $order = FinancingOrder::lockForUpdate()->findOrFail($order);

            if ($request->validated('case') === FinancingOrderProceedCase::ClientWakalaAccepted) {
                $media = $acceptClientWakala->handle($order);

                Trader::driver('dmcc')->getTti($order);

                return $this->successResponse([
                    'wakala_file_url' => $media->previewUrl,
                ]);
            } elseif ($request->validated('case') === FinancingOrderProceedCase::ContractSigned) {
                $order->update([
                    'status' => FinancingOrderStatus::ContractSigned,
                ]);

                $traderOrder = $order->traderOrders->last();
                UpdateFinancialOrderStatus::dispatch(
                    $traderOrder,
                    FinancingOrderStatus::SellingCommodityToCustomer
                )->delay(now()->addMinutes(2));

                Trader::driver($traderOrder->provider)
                    ->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ResponsePtp);

                return $this->successResponse();
            }
        });
    }
}
