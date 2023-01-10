<?php

namespace Tests\Unit\Wallets\Transactions\TransactionTypeHandlers;

use App\Enums\TransactionReason;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionServiceInterface;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use App\Support\Wallets\Transactions\TransactionTypeHandlers\DefaultGenerator;
use Cknow\Money\Money;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class DefaultGeneratorTest extends TestCase
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

        self::$transactionTypeHandler = new DefaultGenerator();
        [self::$company, self::$wallet] = $this->createCompany(2000);
        self::$depositTransaction = app()->make(TransactionServiceInterface::class)->deposit(self::$wallet, Money::parseByDecimal(-100, 'SAR'), 1);
    }

    public function test_default_generator_handler_implements_transaction_type_handler_interface_instance()
    {
        $this->assertInstanceOf(TransactionTypeHandlerInterface::class, self::$transactionTypeHandler);
    }

    public function test_default_generator_generate_message_method_with_all_available_locales_return_string()
    {
        foreach (config('app.locales') as $locale) {
            $transactionDescription = self::$transactionTypeHandler->generateMessage(self::$depositTransaction, $locale);
            $this->assertEquals('', $transactionDescription);
        }
    }

    public function test_default_generator_process_method_return_transaction_model_instance()
    {
        $transaction = self::$transactionTypeHandler->process(
            self::$wallet,
            Money::parseByDecimal(100, 'SAR'),
            TransactionReason::ManualDeposit,
            []
        );
        $this->assertInstanceOf(Transaction::class, $transaction);
    }
}
