<?php

namespace App\Actions\Contracts\Clients;

use App\Models\FinancingOrder;
use Illuminate\Http\Request;

interface SendOtpClientWakala
{
    /**
     * @param  Request  $request
     * @param  FinancingOrder  $order
     * @return bool
     */
    public function handle(Request $request, FinancingOrder $order): string;
}
