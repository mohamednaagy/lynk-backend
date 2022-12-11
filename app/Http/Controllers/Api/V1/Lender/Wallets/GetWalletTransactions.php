<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\GetTransactions;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Wallets\GetTransactionsRequest;
use App\Transformers\TransactionTransformer;
use Illuminate\Http\JsonResponse;

class GetWalletTransactions extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::LenderTransactions, Action::Index])
        );
    }

    public function __invoke(
        GetTransactionsRequest $getTransactionsRequest,
        GetTransactions $getTransactions
    ): JsonResponse {
        $response = $getTransactions->handle();

        return fractal($response, new TransactionTransformer())
            ->parseIncludes([
                'id',
                'date',
                'description',
                'amount',
            ])
            ->respond();
    }
}
