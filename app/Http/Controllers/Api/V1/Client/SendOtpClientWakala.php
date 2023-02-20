<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Contracts\Clients\SendOtpClientWakala as SendOTPClientWakalaInterface;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\SendOtpRequest;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SendOtpClientWakala extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  SendOtpRequest  $request
     * @param  SendOTPClientWakalaInterface  $sendOTPClientWakala
     * @return JsonResponse
     *
     * @throws \Throwable
     */
    public function __invoke(
        SendOtpRequest $request,
        SendOTPClientWakalaInterface $sendOTPClientWakala
    ) {
        return DB::transaction(function () use ($request, $sendOTPClientWakala) {
            $order = FinancingOrder::lockForUpdate()
                ->find($request->validated('order_id'));

            if (! $order || $order->getNationalId() !== $request->validated('national_id')) {
                return $this->errorResponse(
                    __('error.wrong_data'),
                    Response::HTTP_BAD_REQUEST,
                    ErrorCode::WRONG_DATA
                );
            }

            if ($order->status->is(FinancingOrderStatus::PendingApproval)) {
                return $this->errorResponse(
                    __('error.order_still_pending'),
                    Response::HTTP_BAD_REQUEST,
                    ErrorCode::ORDER_STILL_PENDING
                );
            }

            if ($order->status->is(FinancingOrderStatus::Rejected)) {
                return $this->errorResponse(
                    __('error.order_is_rejected'),
                    Response::HTTP_BAD_REQUEST,
                    ErrorCode::ORDER_IS_REJECTED
                );
            }

            $traderOrder = $order->activeTraderOrder()->first();

            if (
                $traderOrder === null
                || $traderOrder->checkOrderStepComplete(FinancingOrderStatus::ClientWakalaCompleted)
            ) {
                return $this->errorResponse(
                    __('error.client_wakala_already_accepted'),
                    Response::HTTP_BAD_REQUEST,
                    ErrorCode::CLIENT_WAKALA_ACCEPTED
                );
            }

            $vid = $sendOTPClientWakala->handle($request, $order);

            return $this->successResponse([
                'vid' => $vid,
            ]);
        });
    }
}
