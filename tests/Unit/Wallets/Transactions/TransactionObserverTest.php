<?php

namespace Tests\Unit\Wallets\Transactions;

use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\Wallet;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;

/**
 * @see \App\Observers\TransactionObserver
 */
class TransactionObserverTest extends TestCase
{
    use InteractsWithCompany, RefreshDatabase;

    protected static Company $lender;

    protected static Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$lender] = $this->createCompany();
        self::$wallet = self::$lender->getWallet(WalletType::CompanyWallet);
    }

    public function test_sets_balance_correctly_for_first_transaction()
    {
        $initialBalance = self::$wallet->balance->convertAndFormatByDecimal(); // e.g. 2000 SAR

        $amount = Money::parseByDecimal(1, self::$wallet->currency);
        $transaction = app(CreateTransactions::class)->handle(self::$wallet, TransactionReason::ManualDeposit, $amount, []);

        $this->assertNotNull($transaction->balance);

        $expectedBalance = $initialBalance + 1;
        $this->assertEquals(
            $expectedBalance,
            $transaction->balance->convertAndFormatByDecimal()
        );
    }

    public function test_updates_balance_correctly_after_multiple_transactions(): void
    {
        $initialBalance = self::$wallet->balance->convertAndFormatByDecimal(); // e.g. 2000 SAR

        $firstTransactionAmount = Money::parseByDecimal(1, self::$wallet->currency);
        app(CreateTransactions::class)->handle(self::$wallet, TransactionReason::ManualDeposit, $firstTransactionAmount, []);

        $secondTransactionAmount = Money::parseByDecimal(-0.5, self::$wallet->currency);
        $secondTransaction = app(CreateTransactions::class)->handle(self::$wallet, TransactionReason::DeliveryConfirmedFee, $secondTransactionAmount, []);

        $expectedBalance = $initialBalance + $firstTransactionAmount->convertAndFormatByDecimal() + $secondTransactionAmount->convertAndFormatByDecimal();

        $this->assertEquals(
            $expectedBalance,
            $secondTransaction->balance->convertAndFormatByDecimal()
        );
    }
}
