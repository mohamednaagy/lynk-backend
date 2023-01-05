<?php

namespace Tests\Unit\Wallets\Transactions\TransactionTypeHandlers;

use App\Enums\TransactionReason;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Generator\ReferenceNumber\ReferenceNumberGenerator;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use App\Support\Wallets\Transactions\TransactionTypeHandlers\OrderCreationFeeType;
use App\Support\Wallets\TransactionService;
use Cknow\Money\Money;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class OrderCreationFeeTypeTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static TransactionTypeHandlerInterface $transactionTypeHandler;

    private static TransactionService $transactionService;

    private static Transaction $depositTransaction;

    private static Company $company;

    private static Wallet $wallet;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$transactionTypeHandler = new OrderCreationFeeType();
        self::$transactionService = new TransactionService(new ReferenceNumberGenerator);
        [self::$company, self::$wallet] = $this->createCompany(2000);
        self::$depositTransaction = self::$transactionService->deposit(self::$wallet, Money::parseByDecimal(-100, 'SAR'), 1);
    }

    public function test_order_creation_fee_handler_implements_transaction_type_handler_interface_instance()
    {
        $this->assertInstanceOf(TransactionTypeHandlerInterface::class, self::$transactionTypeHandler);
    }

    public function test_order_creation_fee_generate_message_method_with_locale_return_string()
    {
        $transactionDescription = self::$transactionTypeHandler->generateMessage(self::$depositTransaction, 'ar');
        $this->assertIsString($transactionDescription);
    }

    public function test_order_creation_fee_process_method_return_transaction_model_instance()
    {
        $transaction = self::$transactionTypeHandler->process(
            self::$wallet,
            Money::parseByDecimal(100, 'SAR'),
            TransactionReason::OrderCreationFee,
            []
        );
        $this->assertInstanceOf(Transaction::class, $transaction);
    }
}
