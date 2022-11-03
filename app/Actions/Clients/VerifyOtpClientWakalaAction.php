<?php

namespace App\Actions\Clients;

use App\Actions\Contracts\Clients\VerifyOtpClientWakala;
use Illuminate\Http\Request;
use Otpify;

class VerifyOtpClientWakalaAction implements VerifyOtpClientWakala
{
    public function handle(Request $request, string $vid, string $code): bool
    {
        Otpify::driver(config('otpify.absher_default'))->verify($request, $vid, $code);

        return true;
    }
}
