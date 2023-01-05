<?php

namespace Tests\Unit\Wallets\Transactions;

use App\Enums\TransactionReason;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Generator\ReferenceNumber\ReferenceNumberGenerator;
use App\Support\Wallets\Contracts\TransactionTypeHandlerInterface;
use App\Support\Wallets\Contracts\TransactionUtilInterface;
use App\Support\Wallets\TransactionService;
use Cknow\Money\Money;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class TransactionUtilTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static TransactionUtilInterface $transactionUtil;

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

        self::$transactionUtil = app(TransactionUtilInterface::class);
        self::$transactionService = new TransactionService(new ReferenceNumberGenerator);
        [self::$company, self::$wallet] = $this->createCompany(2000);
        self::$depositTransaction = self::$transactionService->deposit(self::$wallet, Money::parseByDecimal(-100, 'SAR'), 1);
    }

    public function test_transaction_util_resolve_handler_method_return_transaction_type_handlers_instance()
    {
        $transactionTypeHandler = self::$transactionUtil->resolveHandler(TransactionReason::DepositByEdaat);
        $this->assertInstanceOf(TransactionTypeHandlerInterface::class, $transactionTypeHandler);
    }

    public function test_transaction_util_get_description_method_with_locale_return_string()
    {
        $transactionDescription = self::$transactionUtil->getDescription(self::$depositTransaction, 'ar');
        $this->assertIsString($transactionDescription);
    }

    public function test_transaction_util_get_description_method_without_locale_return_string()
    {
        $transactionDescription = self::$transactionUtil->getDescription(self::$depositTransaction);
        $this->assertIsString($transactionDescription);
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
