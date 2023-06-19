<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\RefundOrderCreationFees;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\TraderOrder;
use App\Support\Wallets\TransactionService;

class RefundOrderCreationFeesAction implements RefundOrderCreationFees
{
    public function __construct(protected TransactionService $transactionService)
    {
    }

    public function handle(TraderOrder $traderOrder)
    {
        $financingOrder = $traderOrder->order;
        $company = $financingOrder->company;
        $wallet = $company->getWallet(WalletType::CompanyWallet);

        $transactions = $wallet->transactions()
            ->whereTraderOrderId($traderOrder->id)
            ->get();

        $transactions->each(function ($transaction) use ($wallet, $financingOrder, $traderOrder) {
            $this->transactionService->deposit(
                $wallet,
                $transaction->amount,
                TransactionReason::RefundAfterCancellation,
                meta: [
                    'financing_order_id' => $financingOrder->id,
                    'trader_order_id' => $traderOrder->id,
                    'financing_order_reference_number' => $financingOrder->reference_number,
                    'amount' => $transaction->amount,
                    'refunded_transaction_id' => $transaction->id,
                ]
            );
        });
    }
}
