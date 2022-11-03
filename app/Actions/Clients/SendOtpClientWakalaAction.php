<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\SendOtpClientWakala;
use App\Models\FinancingOrder;
use Illuminate\Http\Request;
use Otpify;

class SendOtpClientWakalaAction implements SendOtpClientWakala
{
    public function handle(Request $request, FinancingOrder $order): string
    {
        $otpCode = Otpify::driver(config('otpify.absher_default'))->send($request, $order);

        return $otpCode->id;
    }
}
