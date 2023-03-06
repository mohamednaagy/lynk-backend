<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Contracts\Clients\VerifiedClientWakala;
use App\Actions\Contracts\Clients\VerifyOtpClientWakala as VerifyOtpClientWakalaInterface;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\VerifyOtpRequest;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class VerifyOtpClientWakala extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  VerifyOtpRequest  $request
     * @param  VerifyOtpClientWakalaInterface  $verifyOtpClientWakala
     * @param  VerifiedClientWakala  $verifiedClientWakala
     * @return JsonResponse
     *
     * @throws \Throwable
     */
    public function __invoke(
        VerifyOtpRequest $request,
        VerifyOtpClientWakalaInterface $verifyOtpClientWakala,
        VerifiedClientWakala $verifiedClientWakala
    ) {
        return DB::transaction(function () use ($request, $verifiedClientWakala, $verifyOtpClientWakala) {
            $order = FinancingOrder::lockForUpdate()
                ->findOrFail($request->validated('order_id'));

            $traderOrder = $order->activeTraderOrder()->first();

            $canProceed = $order->getNationalId() === $request->validated('national_id')
                && $traderOrder !== null
                && ! $traderOrder->checkOrderStepComplete(FinancingOrderStatus::ClientWakalaCompleted);

            abort_if(! $canProceed, 404);

            abort_if(
                ! $verifyOtpClientWakala->handle($request, $request->validated('vid'), $request->validated('code'), $order),
                404
            );

            return $this->successResponse($verifiedClientWakala->handle($order));
        });
    }
}
