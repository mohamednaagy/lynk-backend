<?php

namespace App\Http\Controllers\Api\V1\LocalMarket;

use App\Http\Controllers\Controller;
use App\Services\LocalMarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalMarketController extends Controller
{


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function initOrder(
        Request $request
    ): JsonResponse {
        return app(LocalMarketService::class)->createInitialOrder($request->company_id, $request->preferred_types, $request->amount, $request->rotation);
    }
}
