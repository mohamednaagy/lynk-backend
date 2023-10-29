<?php

namespace Tests\Unit\Wallets\Transactions\TransactionTypeHandlers;

use App\Enums\TransactionReason;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionServiceInterface;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use App\Support\Wallets\Transactions\TransactionTypeHandlers\OrderCreationFeeType;
use Cknow\Money\Money;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;

class OrderCreationFeeTypeTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany;

    private static TransactionTypeHandlerInterface $transactionTypeHandler;

    private static Transaction $depositTransaction;

    private static Company $company;

    private static Wallet $wallet;

    private static array $messages = [
        'ar' => 'رسوم إنشاء طلب #123456',
        'en' => 'Order #123456 creation fee',
    ];

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$transactionTypeHandler = new OrderCreationFeeType();
        [self::$company, self::$wallet] = $this->createCompany(2000);
        self::$depositTransaction = app()->make(TransactionServiceInterface::class)
            ->deposit(
                self::$wallet,
                Money::parseByDecimal(-10000, Money::getDefaultCurrency()),
                1,
                null,
                [
                    'type' => 'test',
                    'financing_order_id' => '123456',
                ]
            );
    }

    public function test_order_creation_fee_handler_implements_transaction_type_handler_interface_instance()
    {
        $this->assertInstanceOf(TransactionTypeHandlerInterface::class, self::$transactionTypeHandler);
    }

    public function test_order_creation_fee_generate_message_method_with_all_available_locales_return_string()
    {
        foreach (self::$messages as $locale => $message) {
            $transactionDescription = self::$transactionTypeHandler->generateMessage(self::$depositTransaction, $locale);
            $this->assertEquals($message, $transactionDescription);
        }
    }

    public function test_order_creation_fee_process_method_return_transaction_model_instance()
    {
        $transaction = self::$transactionTypeHandler->process(
            self::$wallet,
            Money::parseByDecimal(10000, Money::getDefaultCurrency()),
            TransactionReason::OrderCreationFee,
            null,
            []
        );
        $this->assertInstanceOf(Transaction::class, $transaction);
    }
}
