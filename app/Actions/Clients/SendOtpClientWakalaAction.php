<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\SendOtpClientWakala;
use App\Models\FinancingOrder;
use Illuminate\Http\Request;
use Modules\Otpify\Facades\Otpify;

class SendOtpClientWakalaAction implements SendOtpClientWakala
{
    public function handle(Request $request, FinancingOrder $order): string
    {
        $otpCode = Otpify::driver(config('otpify.default_ni_driver'))->send($request, $order);

        return $otpCode->id;
    }
}
