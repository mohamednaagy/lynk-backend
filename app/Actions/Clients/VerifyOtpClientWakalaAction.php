<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\VerifyOtpClientWakala;
use App\Models\FinancingOrder;
use Illuminate\Http\Request;
use Modules\Otpify\Facades\Otpify;
use Modules\Otpify\Models\OtpifyCode;

class VerifyOtpClientWakalaAction implements VerifyOtpClientWakala
{
    public function handle(Request $request, string $vid, string $code, FinancingOrder $order): bool
    {
        $isVerified = Otpify::driver(config('otpify.default_ni_driver'))
            ->verify(
                $request,
                $vid,
                $code,
                function (Request $request, OtpifyCode $optifyCode) use ($order) {
                    return $optifyCode->otpifiable instanceof FinancingOrder
                        && $order->id === $optifyCode->otpifiable_id
                        && $request->validated('national_id') === $order->getNationalId();
                }
            );

        return $isVerified !== false;
    }
}
