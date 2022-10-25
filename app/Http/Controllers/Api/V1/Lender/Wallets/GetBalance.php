<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Lenders\GetLenderBalance;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetBalance extends Controller
{
    public function __invoke(Request $request, GetLenderBalance $getBalance): JsonResponse
    {
        return $this->successResponse(data: $getBalance->handle(tenant()));
    }
}
