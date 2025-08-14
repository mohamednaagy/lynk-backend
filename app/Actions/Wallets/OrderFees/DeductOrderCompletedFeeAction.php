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
            Log::info('DeductOrderCompletedFeeAction::handle START', [
                'trader_order_id' => $traderOrder->id,
                'financing_order_id' => $traderOrder->financing_order_id,
                'provider' => $traderOrder->provider,
                'status' => $traderOrder->status->value,
            ]);

            $financingOrder = $traderOrder->order;
            if (! $financingOrder) {
                Log::error('DeductOrderCompletedFeeAction: FinancingOrder not found', [
                    'trader_order_id' => $traderOrder->id,
                ]);

                return null;
            }

            $company = $financingOrder->company()->withTrashed()->first();
            if (! $company) {
                Log::error('DeductOrderCompletedFeeAction: Company not found', [
                    'trader_order_id' => $traderOrder->id,
                    'financing_order_id' => $financingOrder->id,
                ]);

                return null;
            }

            Log::info('DeductOrderCompletedFeeAction: Found company and financing order', [
                'trader_order_id' => $traderOrder->id,
                'financing_order_id' => $financingOrder->id,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'order_amount' => $financingOrder->amount->jsonSerialize(),
            ]);

            Log::info('DeductOrderCompletedFeeAction: Attempting to get company wallet', [
                'trader_order_id' => $traderOrder->id,
                'company_id' => $company->id,
                'wallet_type' => WalletType::CompanyWallet,
            ]);

            try {
                $wallet = $company->getWallet(WalletType::CompanyWallet, false);

                Log::info('DeductOrderCompletedFeeAction: Wallet retrieval completed', [
                    'trader_order_id' => $traderOrder->id,
                    'company_id' => $company->id,
                    'wallet_found' => $wallet !== null,
                    'wallet_id' => $wallet?->id,
                ]);

            } catch (\Exception $walletException) {
                Log::error('DeductOrderCompletedFeeAction: Exception during wallet retrieval', [
                    'trader_order_id' => $traderOrder->id,
                    'company_id' => $company->id,
                    'exception_class' => get_class($walletException),
                    'exception_message' => $walletException->getMessage(),
                    'exception_trace' => $walletException->getTraceAsString(),
                ]);
                throw $walletException;
            }

            if (! $wallet) {
                Log::error('DeductOrderCompletedFeeAction: Company wallet not found', [
                    'trader_order_id' => $traderOrder->id,
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'wallet_type_requested' => WalletType::CompanyWallet,
                ]);

                // Let's also check what wallets this company DOES have
                try {
                    $allWallets = $company->wallets()->get();
                    Log::info('DeductOrderCompletedFeeAction: Company existing wallets', [
                        'trader_order_id' => $traderOrder->id,
                        'company_id' => $company->id,
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
                    Log::error('DeductOrderCompletedFeeAction: Failed to retrieve company wallets list', [
                        'trader_order_id' => $traderOrder->id,
                        'company_id' => $company->id,
                        'exception' => $walletListException->getMessage(),
                    ]);
                }

                return null;
            }

            Log::info('DeductOrderCompletedFeeAction: Found wallet', [
                'trader_order_id' => $traderOrder->id,
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
                Log::warning('DeductOrderCompletedFeeAction: Transaction already exists', [
                    'trader_order_id' => $traderOrder->id,
                    'existing_transaction_id' => $existingTransaction->id,
                    'existing_amount' => $existingTransaction->amount->jsonSerialize(),
                ]);

                return $existingTransaction;
            }

            Log::info('DeductOrderCompletedFeeAction: Calculating TieredPricing', [
                'trader_order_id' => $traderOrder->id,
                'company_id' => $company->id,
                'order_amount' => $financingOrder->amount->jsonSerialize(),
            ]);

            $orderCostWithoutVat = TieredPricing::getOrderCostWithoutVat($company, $financingOrder->amount);

            Log::info('DeductOrderCompletedFeeAction: TieredPricing calculated', [
                'trader_order_id' => $traderOrder->id,
                'order_cost_without_vat' => $orderCostWithoutVat->jsonSerialize(),
            ]);

            [$vatAmount, $vatRate] = $this->calculateVatAmount
                ->setAmount($orderCostWithoutVat)
                ->setIsVatIncludedInAmount(false)
                ->handle();

            Log::info('DeductOrderCompletedFeeAction: VAT calculated', [
                'trader_order_id' => $traderOrder->id,
                'vat_amount' => $vatAmount->jsonSerialize(),
                'vat_rate' => $vatRate,
            ]);

            $totalAmountWithVat = $orderCostWithoutVat->add($vatAmount);

            Log::info('DeductOrderCompletedFeeAction: Final amount calculated', [
                'trader_order_id' => $traderOrder->id,
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
                'pricing_tier' => TieredPricing::getPricingTier($company, $financingOrder->amount),
            ];

            Log::info('DeductOrderCompletedFeeAction: Creating wallet transaction', [
                'trader_order_id' => $traderOrder->id,
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

            Log::info('DeductOrderCompletedFeeAction::handle SUCCESS', [
                'trader_order_id' => $traderOrder->id,
                'transaction_id' => $transaction->id,
                'transaction_amount' => $transaction->amount->jsonSerialize(),
                'transaction_reference' => $transaction->reference_number,
                'new_wallet_balance' => $wallet->fresh()->balance->jsonSerialize(),
            ]);

            return $transaction;

        } catch (NoMatchOrderCostAndValueException $e) {
            Log::error('DeductOrderCompletedFeeAction: TieredPricing exception', [
                'trader_order_id' => $traderOrder->id,
                'error' => $e->getMessage(),
                'company_id' => $company->id ?? 'unknown',
                'order_amount' => $financingOrder->amount->jsonSerialize() ?? 'unknown',
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('DeductOrderCompletedFeeAction: Unexpected exception', [
                'trader_order_id' => $traderOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
