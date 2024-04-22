<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\GetTransactions;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Wallets\ListTransactionRequest;
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
        ListTransactionRequest $request,
        GetTransactions $getTransactions
    ): JsonResponse {
        $data = $request->validated();
        $paginatedTransactions = $getTransactions->handle(tenant(), $data);

        tap($paginatedTransactions)->loadZatcaInvoicesMedia();

        return fractal($paginatedTransactions, (new TransactionTransformer()))
            ->parseIncludes([
                'id',
                'date',
                'description',
                'amount',
                'amount_formatted',
                'receipt_url',
            ])
            ->respond();
    }
}
