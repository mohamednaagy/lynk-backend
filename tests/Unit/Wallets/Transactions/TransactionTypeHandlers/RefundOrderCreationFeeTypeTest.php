<?php

namespace Tests\Unit\Wallets\Transactions\TransactionTypeHandlers;

use App\Enums\TransactionReason;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionServiceInterface;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use App\Support\Wallets\Transactions\TransactionTypeHandlers\RefundOrderCreationFeeType;
use Cknow\Money\Money;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;

class RefundOrderCreationFeeTypeTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany;

    private static TransactionTypeHandlerInterface $transactionTypeHandler;

    private static Transaction $depositTransaction;

    private static Company $company;

    private static Wallet $wallet;

    private static array $messages = [
        'ar' => 'استعادة رسوم لطلب المرابحة #123456',
        'en' => 'Refund for trading request #123456',
    ];

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$transactionTypeHandler = new RefundOrderCreationFeeType();
        [self::$company, self::$wallet] = $this->createCompany();
        self::$depositTransaction = app()->make(TransactionServiceInterface::class)
            ->deposit(
                self::$wallet,
                Money::parseByDecimal(11500, Money::getDefaultCurrency()),
                TransactionReason::RefundOrderCreationFee,
                null,
                [
                    'is_vat_included' => true,
                    'financing_order_id' => '123456',
                ]
            );
    }

    public function test_refund_order_creation_fee_handler_implements_transaction_type_handler_interface_instance()
    {
        $this->assertInstanceOf(TransactionTypeHandlerInterface::class, self::$transactionTypeHandler);
    }

    public function test_refund_order_creation_fee_generate_message_method_with_all_available_locales_return_string()
    {
        foreach (self::$messages as $locale => $message) {
            $transactionDescription = self::$transactionTypeHandler->generateMessage(self::$depositTransaction, $locale);
            $this->assertEquals($message, $transactionDescription);
        }
    }

    public function test_refund_order_creation_fee_process_method_return_transaction_model_instance()
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
