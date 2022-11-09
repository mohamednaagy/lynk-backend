<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\MakeOrderProceedRequest;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class MakeOrderProceed extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  MakeOrderProceedRequest  $request
     * @param  AcceptClientWakala  $acceptClientWakala
     * @param  int  $order
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function __invoke(
        MakeOrderProceedRequest $request,
        AcceptClientWakala $acceptClientWakala,
        int $order
    ): JsonResponse {
        return DB::transaction(function () use ($request, $acceptClientWakala, $order) {
            $order = FinancingOrder::lockForUpdate()->findOrFail($order);

            if ($request->validated('case') === FinancingOrderProceedCase::ClientWakalaAccepted) {
                $media = $acceptClientWakala->handle($order);

                $order->update([
                    'status' => FinancingOrderStatus::WaitingPurchasingCommodity,
                ]);

                Trader::driver('dmcc')->getTti($order);

                return $this->successResponse([
                    'wakala_file_url' => $media->getUrl(),
                ]);
            } elseif ($request->validated('case') === FinancingOrderProceedCase::ContractSigned) {
                $order->update([
                    'status' => FinancingOrderStatus::ContractSigned,
                ]);

                return $this->successResponse();
            }

            return $this->successResponse();
        });
    }
}
