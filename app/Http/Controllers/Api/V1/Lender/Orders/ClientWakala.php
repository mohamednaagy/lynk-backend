<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Clients\AskClientWakala;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\AskWakalaRequest;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;

class ClientWakala extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        AskWakalaRequest $request,
        AskClientWakala $askClientWakala,
        FinancingOrder $order
    ): JsonResponse {
        // $askClientWakala->handle($order, $request->validated('wakala_url'));

        return $this->successResponse();
    }
}
