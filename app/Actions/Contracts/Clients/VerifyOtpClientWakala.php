<?php

namespace App\Actions\Contracts\Clients;

use App\Models\FinancingOrder;
use Illuminate\Http\Request;

interface VerifyOtpClientWakala
{
    public function handle(Request $request, string $vid, string $code, FinancingOrder $order): bool;
}
