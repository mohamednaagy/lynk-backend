<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\RefundOrderCreationFees;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\TraderOrder;
use App\Support\Generator\ReferenceNumber\Contracts\ReferenceNumberGeneratorInterface;
use App\Support\Wallets\Contracts\TransactionServiceInterface;

class RefundOrderCreationFeesAction implements RefundOrderCreationFees
{
    public function __construct(
        protected TransactionServiceInterface $transactionService,
        protected ReferenceNumberGeneratorInterface $referenceGenerator
    ) {
    }

    public function handle(TraderOrder $traderOrder)
    {
        $financingOrder = $traderOrder->order;
        $company = $financingOrder->company()->withTrashed()->first();
        $wallet = $company->getWallet(WalletType::CompanyWallet);

        $transactions = $wallet->transactions()
            ->reasons([
                TransactionReason::OrderCreationFee,
                TransactionReason::VatPercentageFee,
            ])
            ->whereTraderOrderId($traderOrder->id)
            ->get();

        $reference = $this->referenceGenerator->generate();
        $transactions->each(function ($transaction) use ($wallet, $financingOrder, $traderOrder, $reference) {
            $wallet->deposit(
                $transaction->amount,
                $this->getTransactionReasonForRefund($transaction),
                $reference,
                [
                    'financing_order_id' => $financingOrder->id,
                    'trader_order_id' => $traderOrder->id,
                    'refunded_transaction_id' => $transaction->id,
                ]
            );
        });
    }

    protected function getTransactionReasonForRefund($refunedTransaction)
    {
        return match ($refunedTransaction->reason) {
            TransactionReason::OrderCreationFee => TransactionReason::RefundOrderCreationFee,
            TransactionReason::VatPercentageFee => TransactionReason::RefundVatPercentageFee,
        };
    }
}
