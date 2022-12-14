<?php

namespace Tests\Unit\Wallet;

use App\Models\Company;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\Wallet;
use App\Support\Generator\ReferenceNumber\ReferenceNumberGenerator;
use App\Support\Wallets\TransactionService;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;

    private static TransactionService $transactionService;

    private static Company $company;

    private static Company $secondCompany;

    private static Wallet $wallet;

    private static Wallet $secondWallet;

    private static array $walletInformation;

    public function setUp(): void
    {
        parent::setUp();

        self::$transactionService = new TransactionService(new ReferenceNumberGenerator);
        [self::$company, self::$wallet] = $this->createCompany(2000);
        [self::$secondCompany, self::$secondWallet] = $this->createCompany(2000, ['company_cr' => '12345678911']);
    }

    public function test_transaction_service_withdraw_method_return_transaction_instance()
    {
        $transaction = self::$transactionService->withdraw(self::$wallet, Money::parseByDecimal(100, 'SAR'), 1);
        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    public function test_transaction_service_withdraw_method_always_convert_money_to_negative()
    {
        $transaction = self::$transactionService->withdraw(self::$wallet, Money::parseByDecimal(100, 'SAR'), 1);

        $this->assertTrue($transaction->amount->getAmount() == -10000);
    }

    public function test_transaction_service_deposit_method_return_transaction_instance()
    {
        $transaction = self::$transactionService->withdraw(self::$wallet, Money::parseByDecimal(100, 'SAR'), 1);

        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    public function test_transaction_service_deposit_method_always_convert_money_to_positive()
    {
        $transaction = self::$transactionService->deposit(self::$wallet, Money::parseByDecimal(-100, 'SAR'), 1);

        $this->assertTrue($transaction->amount->getAmount() == 10000);
    }

    public function test_transaction_service_transfer_method_return_transaction_instance()
    {
        $transfer = self::$transactionService->transfer(self::$wallet, self::$secondWallet, Money::parseByDecimal(-100, 'SAR'), 1);

        $this->assertInstanceOf(Transfer::class, $transfer);
    }

    public function test_transaction_service_transfer_money_transfared_successfully()
    {
        $amount = Money::parseByDecimal(100, 'SAR');
        $fromWallet = self::$wallet;
        $toWallet = self::$secondWallet;
        $toWalletBalanceBeforeTransfer = self::$secondWallet->balance;

        self::$transactionService->transfer($fromWallet, $toWallet, $amount, 1);

        $this->assertTrue(
            $toWalletBalanceBeforeTransfer->add($amount)
                ->equals($toWallet->balance)
        );
    }
}
