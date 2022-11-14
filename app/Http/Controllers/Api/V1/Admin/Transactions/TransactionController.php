<?php

namespace App\Http\Controllers\Api\V1\Admin\Transactions;

use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Transactions\StoreTransactionRequest;
use App\Models\Company;
use App\Transformers\TransactionTransformer;

class TransactionController extends Controller
{
    public function index(Company $company)
    {
        return fractal($company->transactions()->paginate(), new TransactionTransformer())->respond();
    }

    public function store(Company $company, CreateTransactions $createTransactions, StoreTransactionRequest $storeTransactionRequest)
    {
        $createTransactions->handle(
            $company->getWallet(WalletType::CompanyWallet),
            TransactionReason::ManualDeposit,
            $storeTransactionRequest->validated('amount'),
            $storeTransactionRequest->safe(['description_en', 'description_ar']),
            [$storeTransactionRequest->validated('attachment')]
        );

        return $this->successResponse();
    }
}
