<?php

namespace App\Http\Controllers\Api\V1\Admin\Orders;

use App\Actions\Contracts\Lenders\GetLenderBalance;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

// __REVIEW__ change filename to GetCompanyBalance
// __REVIEW__ path app/Http/Controllers/Api/V1/Admin/Companies
class GetBalance extends Controller
{
    /**
     * @param  Company  $company
     * @param  GetLenderBalance  $getBalance
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Company $company, GetLenderBalance $getBalance): JsonResponse
    {
        $balances = $getBalance->handle($company);

        return $this->successResponse(data: [
            'balance' => number_format($balances['balance'], 2),
            'available_orders' => $balances['availableOrders'],
        ]);
    }
}
