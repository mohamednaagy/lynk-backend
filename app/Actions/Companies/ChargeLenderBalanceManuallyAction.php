<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\ChargeLenderBalanceManually;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\GenerateVoucherInvoice;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\Company;
use Cknow\Money\Money;
use Illuminate\Support\Arr;

class ChargeLenderBalanceManuallyAction implements ChargeLenderBalanceManually
{
    public function __construct(
        protected CreateTransactions $createTransactions,
        protected GenerateVoucherInvoice $generateVoucherInvoice,
    ) {
    }

    public function handle(Company $company, array $data)
    {
        $wallet = $company->getWallet(WalletType::CompanyWallet);

        $transaction = $this->createTransactions->handle(
            $wallet,
            TransactionReason::ManualDeposit,
            Money::parseByDecimal(Arr::get($data, 'amount'), $wallet->currency),
            Arr::only($data, ['description_en', 'description_ar'])
        );

        $transaction->addMedia(Arr::get($data, 'attachment'))
            ->toMediaCollection(TransactionMediaCollection::Attachments);

        $this->generateVoucherInvoice->handle($transaction);

        return $transaction;
    }
}
