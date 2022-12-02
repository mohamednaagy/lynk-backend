<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\DeductOrderCreationFee;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\FinancingOrder;
use Cknow\Money\Money;
use Illuminate\Support\Facades\Config;

class DeductOrderCreationFeeAction implements DeductOrderCreationFee
{
    protected $createTransactions;

    public function __construct(CreateTransactions $createTransactions)
    {
        $this->createTransactions = $createTransactions;
    }

    public function handle(FinancingOrder $financingOrder)
    {
        $company = $financingOrder->company;
        $wallet = $company->getWallet(WalletType::CompanyWallet);

        return $this->createTransactions->handle(
            $wallet,
            TransactionReason::OrderCreationFee,
            Money::parseByDecimal($company->order_cost, Config::get('app.currency', 'SAR')),
            [
                'financing_order_id' => $financingOrder->id,
                'reference_number ' => $financingOrder->reference_number,
                'amount' => $financingOrder->amount,
                'order_cost' => $company->order_cost,
            ]
        );
    }
}
