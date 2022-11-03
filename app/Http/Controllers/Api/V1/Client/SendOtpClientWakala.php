<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Contracts\Clients\SendOtpClientWakala as SendOTPClientWakalaInterface;
use App\Http\Controllers\Controller;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SendOtpClientWakala extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @param  FinancingOrder  $order
     * @param  string  $nationalId
     * @param  SendOTPClientWakalaInterface  $sendOTPClientWakala
     * @return JsonResponse
     */
    public function __invoke(
        Request $request,
        FinancingOrder $order,
        string $nationalId,
        SendOTPClientWakalaInterface $sendOTPClientWakala
    ) {
        abort_if($order->getNationalId() !== $nationalId, 404);

        try {
            $vid = $sendOTPClientWakala->handle($request, $order);

            return $this->successResponse([
                'vid' => $vid,
            ]);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }
}
