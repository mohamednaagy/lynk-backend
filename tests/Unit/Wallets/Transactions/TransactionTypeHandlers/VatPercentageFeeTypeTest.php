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
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;

class VatPercentageFeeTypeTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany;

    private static TransactionTypeHandlerInterface $transactionTypeHandler;

    private static Transaction $depositTransaction;

    private static Company $company;

    private static Wallet $wallet;

    private static array $messages = [
        'ar' => 'ضريبة القيمة المضافة (15%) للطلب #1',
        'en' => 'VAT charges (15%) for order #1',
    ];

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$transactionTypeHandler = new VatPercentageFeeType();
        [self::$company, self::$wallet] = $this->createCompany(2000);
        self::$depositTransaction = app()->make(TransactionServiceInterface::class)
            ->deposit(
                self::$wallet,
                Money::parseByDecimal(-100, 'SAR'),
                1,
                null,
                [
                    'transaction_id' => '1',
                    'financing_order_id' => '1',
                    'vat_rate' => 0.15,
                ]
            );
    }

    public function test_vat_percentage_fee_handler_implements_transaction_type_handler_interface_instance()
    {
        $this->assertInstanceOf(TransactionTypeHandlerInterface::class, self::$transactionTypeHandler);
    }

    public function test_vat_percentage_fee_generate_message_method_with_all_available_locales_return_string()
    {
        foreach (self::$messages as $locale => $message) {
            $transactionDescription = self::$transactionTypeHandler->generateMessage(self::$depositTransaction, $locale);
            $this->assertEquals($message, $transactionDescription);
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
