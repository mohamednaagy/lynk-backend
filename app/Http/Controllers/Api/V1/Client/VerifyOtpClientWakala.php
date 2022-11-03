<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Contracts\Clients\VerifiedClientWakala;
use App\Actions\Contracts\Clients\VerifyOtpClientWakala as VerifyOtpClientWakalaInterface;
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
     * @param  FinancingOrder  $order
     * @param  string  $nationalId
     * @param  VerifyOtpClientWakalaInterface  $verifyOtpClientWakala
     * @param  VerifiedClientWakala  $verifiedClientWakala
     * @return JsonResponse
     */
    public function __invoke(
        VerifyOtpRequest $request,
        VerifyOtpClientWakalaInterface $verifyOtpClientWakala,
        VerifiedClientWakala $verifiedClientWakala
    ) {
        return DB::transaction(function () use ($request, $verifiedClientWakala, $verifyOtpClientWakala) {
            $order = FinancingOrder::lockForUpdate()
                ->findOrFail($request->validated('order_id'));

            abort_if($order->getNationalId() !== $request->validated('national_id'), 404);

            $verifyOtpClientWakala->handle($request, $request->validated('vid'), $request->validated('code'), $order);

            return $this->successResponse($verifiedClientWakala->handle($order));
        });
    }
}
