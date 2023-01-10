<?php

namespace Tests\Unit\Wallets\Transactions\TransactionTypeHandlers;

use App\Enums\TransactionReason;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionServiceInterface;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use App\Support\Wallets\Transactions\TransactionTypeHandlers\VatPercentageFeeType;
use Cknow\Money\Money;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class VatPercentageFeeTypeTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static TransactionTypeHandlerInterface $transactionTypeHandler;

    private static Transaction $depositTransaction;

    private static Company $company;

    private static Wallet $wallet;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$transactionTypeHandler = new VatPercentageFeeType();
        [self::$company, self::$wallet] = $this->createCompany(2000);
        self::$depositTransaction = app()->make(TransactionServiceInterface::class)->deposit(self::$wallet, Money::parseByDecimal(-100, 'SAR'), 1);
    }

    public function test_vat_percentage_fee_handler_implements_transaction_type_handler_interface_instance()
    {
        $this->assertInstanceOf(TransactionTypeHandlerInterface::class, self::$transactionTypeHandler);
    }

    public function test_vat_percentage_fee_generate_message_method_with_all_available_locales_return_string()
    {
        foreach (config('app.locales') as $locale) {
            $transactionDescription = self::$transactionTypeHandler->generateMessage(self::$depositTransaction, $locale);
            $items = Arr::only(self::$depositTransaction->meta, ['transaction_id', 'financing_order_id', 'vat_rate']);
            $this->assertEquals(
                __('transaction-description.vat_percentage', [
                    'order_id' => $items['financing_order_id'] ?? '',
                    'vat_percentage' => ($items['vat_rate'] ?? 0) * 100,
                ], $locale),
                $transactionDescription
            );
        }
    }

    public function test_vat_percentage_fee_process_method_return_transaction_model_instance()
    {
        $transaction = self::$transactionTypeHandler->process(
            self::$wallet,
            Money::parseByDecimal(100, 'SAR'),
            TransactionReason::VatPercentageFee,
            []
        );
        $this->assertInstanceOf(Transaction::class, $transaction);
    }
}
