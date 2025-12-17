<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Actions\Contracts\Lenders\GetLenderBalance as GetLenderBalanceInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\Lender;
use Illuminate\Http\JsonResponse;

class GetLenderBalance extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderWallet, Action::Show, Action::Manage])
        );
    }

    public function __invoke(Lender $lender, GetLenderBalanceInterface $getBalance): JsonResponse
    {
        $balances = $getBalance->handle($lender);

        return $this->successResponse(data: [
            'company_name' => $lender->name,
            'balance' => $balances['balance']->convertAndFormatByDecimal(),
            'balance_formatted' => $balances['balance']->convertAndFormatByDecimal(separator: ','),
        ]);
    }
}
