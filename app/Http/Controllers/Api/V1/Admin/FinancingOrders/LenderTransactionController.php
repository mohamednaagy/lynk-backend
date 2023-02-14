<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancingOrder;
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

    /**
     * @param  Company  $lender
     * @return JsonResponse
     */
    public function index(Company $lender): JsonResponse
    {
        $transactions = $lender->transactions(WalletType::CompanyWallet)->paginate();

        tap($transactions)->transform(function ($transaction) {
            if (array_key_exists('financing_order_id', $transaction->meta)) {
                return $transaction->setRelation('financingOrder',
                    FinancingOrder::find($transaction->meta['financing_order_id'])
                );
            }

            return $transaction;
        });

        return fractal(
            $transactions,
            new TransactionTransformer()
        )
            ->parseIncludes([
                'id',
                'date',
                'description',
                'amount',
                'receipt',
            ])
            ->respond();
    }
}
