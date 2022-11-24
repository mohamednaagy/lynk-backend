<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\ChargeBalanceManually;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\Company;
use Illuminate\Support\Arr;

class ChargeBalanceManuallyAction implements ChargeBalanceManually
{
    public function __construct(protected CreateTransactions $createTransactions)
    {
    }

    public function handle(Company $company, array $data)
    {
        $transaction = $this->createTransactions->handle(
            $company->getWallet(WalletType::CompanyWallet),
            TransactionReason::ManualDeposit,
            Arr::get($data, 'amount'),
            Arr::only($data, ['description_en', 'description_ar'])
        );

        $transaction->addMedia(Arr::get($data, 'attachment'))
            ->toMediaCollection(TransactionMediaCollection::Attachments);

        return $transaction;
    }
}
