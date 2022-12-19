<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Contracts\Clients\SendOtpClientWakala as SendOTPClientWakalaInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\SendOtpRequest;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                ->findOrFail($request->validated('order_id'));

            $canProceed = $order->getNationalId() === $request->validated('national_id')
                && $order->client_wakala_accepted_at === null;
            abort_if(! $canProceed, 404);

            $vid = $sendOTPClientWakala->handle($request, $order);

            return $this->successResponse([
                'vid' => $vid,
            ]);
        });
    }
}
