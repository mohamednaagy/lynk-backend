<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Lenders\GetLenderBalance;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class GetCompanyBalance extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderWallet, Action::Show, Action::Manage])
        );
    }

    /**
     * @param  Company  $company
     * @param  GetLenderBalance  $getBalance
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Company $company, GetLenderBalance $getBalance): JsonResponse
    {
        $balances = $getBalance->handle($company);

        return $this->successResponse(data: [
            'balance' => number_format($balances['balance']->formatByDecimal(), 2),
            'available_orders' => $balances['availableOrders'],
        ]);
    }
}
