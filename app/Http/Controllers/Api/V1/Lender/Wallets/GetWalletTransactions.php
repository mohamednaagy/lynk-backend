<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\GetTransactions;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
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
        GetTransactions $getTransactions
    ): JsonResponse {
        $paginatedTransactions = $getTransactions->handle(tenant());

        tap($paginatedTransactions)->loadZatcaInvoicesMedia();

        return fractal($paginatedTransactions, new TransactionTransformer())
            ->parseIncludes([
                'id',
                'date',
                'description',
                'amount',
                'receipt_url',
            ])
            ->respond();
    }
}
