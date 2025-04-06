<?php

namespace App\Actions\Contracts\Clients;

use App\Models\FinancingOrder;
use Illuminate\Http\Request;

interface SendOtpClientWakala
{
    /**
     * @return bool
     */
    public function handle(Request $request, FinancingOrder $order): string;
}
