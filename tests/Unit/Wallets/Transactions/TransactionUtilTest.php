<?php

namespace Tests\Unit\Wallets\Transactions;

use App\Enums\TransactionReason;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionServiceInterface;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use App\Support\Wallets\Contracts\TransactionUtilInterface;
use Cknow\Money\Money;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class TransactionUtilTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static TransactionUtilInterface $transactionUtil;

    private static Transaction $depositTransaction;

    private static Company $company;

    private static Wallet $wallet;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$transactionUtil = app(TransactionUtilInterface::class);
        [self::$company, self::$wallet] = $this->createCompany(2000);
        self::$depositTransaction = app()->make(TransactionServiceInterface::class)
            ->deposit(
                self::$wallet,
                Money::parseByDecimal(-100, 'SAR'),
                1,
                null,
                [
                    'type' => 'test',
                    'order_number' => '123456',
                ]
            );
    }

    public function test_transaction_util_resolve_handler_method_return_transaction_type_handlers_instance()
    {
        $transactionTypeHandler = self::$transactionUtil->resolveHandler(TransactionReason::DepositByEdaat);
        $this->assertInstanceOf(TransactionTypeHandlerInterface::class, $transactionTypeHandler);
    }

    public function test_transaction_util_get_description_method_with_all_available_locales_return_string()
    {
        foreach (config('app.locales') as $locale) {
            $transactionDescription = self::$transactionUtil->getDescription(self::$depositTransaction, $locale);
            $items = Arr::only(self::$depositTransaction->meta, ['type', 'order_number']);
            $this->assertEquals(
                __('transaction-description.order_creation_fee', [
                    'order_number' => $items['order_number'] ?? '',
                ], $locale),
                $transactionDescription
            );
        }
    }

    public function test_transaction_util_get_description_method_without_locale_return_string()
    {
        $transactionDescription = self::$transactionUtil->getDescription(self::$depositTransaction);
        $items = Arr::only(self::$depositTransaction->meta, ['type', 'order_number']);
        $this->assertEquals(
            __('transaction-description.order_creation_fee', [
                'order_number' => $items['order_number'] ?? '',
            ], config('app.locale')),
            $transactionDescription
        );
    }

    public function test_transaction_util_process_method_return_transaction_model_instance()
    {
        $transaction = self::$transactionUtil->process(
            self::$wallet,
            Money::parseByDecimal(100, 'SAR'),
            TransactionReason::ManualDeposit,
            []
        );
        $this->assertInstanceOf(Transaction::class, $transaction);
    }
}
