<?php

namespace App\Actions\Contracts\Clients;

use Illuminate\Http\Request;

interface VerifyOtpClientWakala
{
    /**
     * @param  Request  $request
     * @param  string  $vid
     * @param  string  $code
     * @return bool
     */
    public function handle(Request $request, string $vid, string $code): bool;
}
