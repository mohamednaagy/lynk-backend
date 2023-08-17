<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\ChargeLenderBalanceManually;
use App\Actions\Contracts\Companies\GetVatAmount;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\GenerateVoucherReceipt;
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
        protected GenerateVoucherReceipt $generateVoucherReceipt,
        protected GetVatAmount $getVatAmount
    ) {
    }

    public function handle(Company $company, array $data)
    {
        $wallet = $company->getWallet(WalletType::CompanyWallet);
        $totalAmount = Money::parseByDecimal(Arr::get($data, 'amount'), $wallet->currency);
        [$vatAmount, $vatRate] = $this->getVatAmount->handle($totalAmount);

        $transaction = $this->createTransactions->handle(
            $wallet,
            TransactionReason::ManualDeposit,
            $totalAmount->subtract($vatAmount),
            Arr::only($data, ['description_en', 'description_ar'])
        );

        $vatTransaction = $this->createTransactions->handle(
            $wallet,
            TransactionReason::ManualDeposit,
            $vatAmount,
            [
                __('transaction-description.vat_percentage_recharge', [
                    'vat_percentage' => $vatRate * 100,
                ], 'en'),
                __('transaction-description.vat_percentage_recharge', [
                    'vat_percentage' => $vatRate * 100,
                ], 'ar'),
            ]
        );

        $transaction->addMedia(Arr::get($data, 'attachment'))
            ->toMediaCollection(TransactionMediaCollection::Attachments);

        $this->generateVoucherReceipt->handle($transaction);
        $this->generateVoucherReceipt->handle($vatTransaction);

        return $transaction;
    }
}
