<?php

namespace App\Actions\Wallets\OrderFees;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\OrderFees\DeductOrderCompletedFee;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Exceptions\NoMatchOrderCostAndValueException;
use App\Models\TieredPricing;
use App\Models\TraderOrder;
use Illuminate\Support\Facades\Log;

class DeductOrderCompletedFeeAction implements DeductOrderCompletedFee
{
    public function __construct(
        protected CreateTransactions $createTransactions,
        protected CalculateVatAmount $calculateVatAmount,
        protected GetProjectSettings $getProjectSettings,
    ) {}

    /**
     * @throws NoMatchOrderCostAndValueException
     */
    public function handle(TraderOrder $traderOrder)
    {
        try {
            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('DeductOrderCompletedFeeAction::handle START', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'provider' => $traderOrder->provider,
                'status' => $traderOrder->status->value,
                'reference_number' => $traderOrder->reference_number,
            ]);

            $financingOrder = $traderOrder->order;
            if (! $financingOrder) {
                Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(formatLogTitle('DeductOrderCompletedFeeAction: financing order not found', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $traderOrder->id,
                    'reference_number' => $traderOrder->reference_number,
                ]);

                return null;
            }

            $lender = $financingOrder->lender()->withTrashed()->first();
            if (! $lender) {
                Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(formatLogTitle('DeductOrderCompletedFeeAction: Lender not found', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $traderOrder->id,
                ]);

                return null;
            }

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('DeductOrderCompletedFeeAction: Found lender and financing order', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'lender_id' => $lender->id,
                'lender_name' => $lender->name,
                'order_amount' => $financingOrder->amount->jsonSerialize(),
            ]);

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('DeductOrderCompletedFeeAction: Attempting to get company wallet', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'lender_id' => $lender->id,
                'wallet_type' => WalletType::CompanyWallet,
            ]);

            try {
                $wallet = $lender->getWallet(WalletType::CompanyWallet, false);

                Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('DeductOrderCompletedFeeAction: Wallet retrieval completed', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $traderOrder->id,
                    'lender_id' => $lender->id,
                    'wallet_found' => $wallet !== null,
                    'wallet_id' => $wallet?->id,
                ]);

            } catch (\Exception $walletException) {
                Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(formatLogTitle('DeductOrderCompletedFeeAction: Exception during wallet retrieval', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $traderOrder->id,
                    'lender_id' => $lender->id,
                    'exception_class' => get_class($walletException),
                    'message' => $walletException->getMessage(),
                    'trace' => $walletException->getTraceAsString(),
                ]);
                throw $walletException;
            }

            if (! $wallet) {
                Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(formatLogTitle('DeductOrderCompletedFeeAction: Company wallet not found', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $traderOrder->id,
                    'lender_id' => $lender->id,
                    'company_name' => $lender->name,
                    'wallet_type_requested' => WalletType::CompanyWallet,
                ]);

                // Let's also check what wallets this company DOES have
                try {
                    $allWallets = $lender->wallets()->get();
                    Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('DeductOrderCompletedFeeAction: Company existing wallets', $traderOrder), [
                        'financingOrderId' => $traderOrder->financing_order_id,
                        'traderOrderId' => $traderOrder->id,
                        'lender_id' => $lender->id,
                        'total_wallets' => $allWallets->count(),
                        'wallet_details' => $allWallets->map(function ($w) {
                            return [
                                'id' => $w->id,
                                'type' => $w->type,
                                'currency' => $w->currency,
                            ];
                        })->toArray(),
                    ]);
                } catch (\Exception $walletListException) {
                    Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(formatLogTitle('DeductOrderCompletedFeeAction: Failed to retrieve company wallets list', $traderOrder), [
                        'financingOrderId' => $traderOrder->financing_order_id,
                        'traderOrderId' => $traderOrder->id,
                        'lender_id' => $lender->id,
                        'message' => $walletListException->getMessage(),
                        'trace' => $walletListException->getTraceAsString(),
                    ]);
                }

                return null;
            }

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('DeductOrderCompletedFeeAction: Found wallet', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'wallet_id' => $wallet->id,
                'wallet_currency' => $wallet->currency,
                'current_balance' => $wallet->balance->jsonSerialize(),
            ]);

            // Check for existing transaction to avoid duplicates
            $existingTransaction = $wallet->transactions()
                ->where('meta->trader_order_id', $traderOrder->id)
                ->where('reason', TransactionReason::OrderCreationFee)
                ->first();

            if ($existingTransaction) {
                Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->warning(formatLogTitle('DeductOrderCompletedFeeAction: Transaction already exists', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $traderOrder->id,
                    'existing_transaction_id' => $existingTransaction->id,
                    'existing_amount' => $existingTransaction->amount->jsonSerialize(),
                ]);

                return $existingTransaction;
            }

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('DeductOrderCompletedFeeAction: Calculating TieredPricing', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'lender_id' => $lender->id,
                'order_amount' => $financingOrder->amount->jsonSerialize(),
            ]);

            $orderCostWithoutVat = TieredPricing::getOrderCostWithoutVat($lender, $financingOrder->amount);

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('DeductOrderCompletedFeeAction: TieredPricing calculated', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'order_cost_without_vat' => $orderCostWithoutVat->jsonSerialize(),
            ]);

            $vatRate = $this->getProjectSettings->handle()->getVatRate();
            $vatAmount = TieredPricing::getVatAmount($lender, $financingOrder->amount, $orderCostWithoutVat);

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('DeductOrderCompletedFeeAction: VAT calculated', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'vat_amount' => $vatAmount->jsonSerialize(),
                'vat_rate' => $vatRate,
            ]);

            $totalAmountWithVat = $orderCostWithoutVat->add($vatAmount);

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('DeductOrderCompletedFeeAction: Final amount calculated', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'total_amount_with_vat' => $totalAmountWithVat->jsonSerialize(),
            ]);

            $transactionMeta = [
                'financing_order_id' => $financingOrder->id,
                'trader_order_id' => $traderOrder->id,
                'reference_number ' => $financingOrder->reference_number,
                'amount' => $financingOrder->amount,
                'vat_percentage' => $vatRate * 100,
                'vat_amount' => $vatAmount,
                'order_cost' => $orderCostWithoutVat,
                'vat_rate' => $vatRate,
                'is_vat_included' => true,
                'pricing_tier' => TieredPricing::getPricingTier($lender, $financingOrder->amount),
            ];

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('DeductOrderCompletedFeeAction: Creating wallet transaction', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'wallet_id' => $wallet->id,
                'amount' => $totalAmountWithVat->jsonSerialize(),
                'transaction_reason' => TransactionReason::OrderCreationFee,
                'meta' => $transactionMeta,
            ]);

            $transaction = $this->createTransactions->handle(
                $wallet,
                TransactionReason::OrderCreationFee,
                $totalAmountWithVat,
                $transactionMeta
            );

            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('DeductOrderCompletedFeeAction::handle SUCCESS', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'transaction_id' => $transaction->id,
                'transaction_amount' => $transaction->amount->jsonSerialize(),
                'transaction_reference' => $transaction->reference_number,
                'new_wallet_balance' => $wallet->fresh()->balance->jsonSerialize(),
            ]);

            return $transaction;

        } catch (NoMatchOrderCostAndValueException $e) {
            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(formatLogTitle('DeductOrderCompletedFeeAction: TieredPricing exception', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'error' => $e->getMessage(),
                'lender_id' => $lender->id ?? 'unknown',
                'order_amount' => $financingOrder->amount->jsonSerialize() ?? 'unknown',
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(formatLogTitle('error at DeductOrderCompletedFeeAction: Unexpected exception', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
