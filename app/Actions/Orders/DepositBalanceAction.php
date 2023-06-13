<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\DepositBalance;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\FinancingOrder;
use App\Support\Wallets\TransactionService;

class DepositBalanceAction implements DepositBalance
{
    public function __construct(protected TransactionService $transactionService)
    {
    }

    public function handle(FinancingOrder $financingOrder)
    {
        $company = $financingOrder->company;
        $wallet = $company->getWallet(WalletType::CompanyWallet);
        $traderOrder = $financingOrder->activeTraderOrder()->first();

        $transactions = $wallet->transactions()
            ->withTraderOrder($traderOrder->id)
            ->get();

        $transactions->each(function ($transaction) use ($wallet, $financingOrder, $traderOrder) {
            $this->transactionService->deposit(
                $wallet,
                $transaction->amount,
                TransactionReason::RefundAfterCancellation,
                $financingOrder->reference_number,
                [
                    'financing_order_id' => $financingOrder->id,
                    'trader_order_id' => $traderOrder->id,
                    'reference_number ' => $financingOrder->reference_number,
                    'amount' => $transaction->amount,
                    'refunded_transaction' => $transaction,
                ]
            );
        });
    }
}
