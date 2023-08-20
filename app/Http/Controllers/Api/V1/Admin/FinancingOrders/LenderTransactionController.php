<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\Subject;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
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

    public function index(Company $lender): JsonResponse
    {
        $transactions = $lender->transactions(WalletType::CompanyWallet)
            ->with([
                'media' => fn ($query) => $query->where('collection_name', TransactionMediaCollection::VoucherReceipt)
                    ->orWhere('collection_name', TransactionMediaCollection::RechargeReceipt),
            ])
            ->paginate();

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
                'receipt_url',
            ])
            ->respond();
    }
}
