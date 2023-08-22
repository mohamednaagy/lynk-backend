<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\GetTransactions;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\WalletType;
use App\Models\Company;

class GetTransactionsAction implements GetTransactions
{
    public function handle(Company $company): mixed
    {
        return $company->transactions(WalletType::CompanyWallet)
            ->with([
                'media' => fn ($query) => $query->where('collection_name', TransactionMediaCollection::VoucherReceipt)
                    ->orWhere('collection_name', TransactionMediaCollection::RechargeReceipt),
            ])
            ->paginate();
    }
}
