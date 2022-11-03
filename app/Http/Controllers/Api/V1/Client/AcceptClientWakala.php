<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Contracts\Clients\AcceptClientWakala as AcceptWakalaInterface;
use App\Http\Controllers\Controller;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use  Illuminate\Http\Request;

class AcceptClientWakala extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @param  FinancingOrder  $order
     * @param  AcceptWakalaInterface  $acceptClientWakala
     * @return JsonResponse
     */
    public function __invoke(
        Request $request,
        FinancingOrder $order,
        AcceptWakalaInterface $acceptClientWakala
    ) {
        $acceptClientWakala->handle($order, $request->bearerToken());

        return $this->successResponse();
    }
}
