<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Contracts\Clients\VerifiedClientWakala;
use App\Actions\Contracts\Clients\VerifyOtpClientWakala as VerifyOtpClientWakalaInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\VerifyOtpRequest;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;

class VerifyOtpClientWakala extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  VerifyOtpRequest  $request
     * @param  FinancingOrder  $order
     * @param  string  $nationalId
     * @param  VerifyOtpClientWakalaInterface  $verifyOtpClientWakala
     * @param  VerifiedClientWakala  $verifiedClientWakala
     * @return JsonResponse
     */
    public function __invoke(
        VerifyOtpRequest $request,
        FinancingOrder $order,
        string $nationalId,
        VerifyOtpClientWakalaInterface $verifyOtpClientWakala,
        VerifiedClientWakala $verifiedClientWakala
    ) {
        abort_if($order->getNationalId() !== $nationalId, 404);

        try {
            $verifyOtpClientWakala->handle($request, $request->validated('vid'), $request->validated('code'));

            return $this->successResponse($verifiedClientWakala->handle($order));
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }
}
