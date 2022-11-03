<?php

namespace App\Actions\Contracts\Clients;

use App\Models\FinancingOrder;
use Illuminate\Http\Request;

interface VerifyOtpClientWakala
{
    /**
     * @param  Request  $request
     * @param  string  $vid
     * @param  string  $code
     * @return bool
     */
    public function handle(Request $request, string $vid, string $code, FinancingOrder $order): bool;
}
