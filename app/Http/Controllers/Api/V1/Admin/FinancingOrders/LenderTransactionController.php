<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Actions\Contracts\Wallets\GetTransactions;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\ListTransactionRequest;
use App\Models\Company;
use App\Transformers\TransactionTransformer;
use Illuminate\Http\JsonResponse;

class LenderTransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::LenderTransactions, Action::Index, Action::Manage])
        )->only('index');
    }

    public function index(ListTransactionRequest $request, Company $lender, GetTransactions $getTransactions): JsonResponse
    {
        $data = $request->validated();
        $transactions = $getTransactions->handle($lender, $data);

        tap($transactions)->loadZatcaInvoicesMedia();

        return fractal(
            $transactions,
            new TransactionTransformer()
        )
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
