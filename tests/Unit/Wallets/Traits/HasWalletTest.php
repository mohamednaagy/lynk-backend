<?php

namespace Tests\Unit\Wallets\Traits;

use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Enums\TransactionReason;
use App\Models\Company;
use App\Models\Transaction;
use App\Support\Money\Money;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class HasWalletTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    /**
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::$company = $this->createCompanyWithoutWallet();
    }

    public function test_has_wallet_get_wallet()
    {
        $wallet = self::$company->getWallet('test-wallet');
        $this->assertNull($wallet);

        self::$company->createWallet('test-wallet', Money::getDefaultCurrency());
        $this->assertNotNull(self::$company->getWallet('test-wallet'));
    }

    public function test_has_wallet_get_wallet_or_fail()
    {
        $this->expectException(ModelNotFoundException::class);
        self::$company->getWalletOrFail('test-wallet');

        self::$company->createWallet('test-wallet', Money::getDefaultCurrency());
        $this->assertNotNull(self::$company->getWalletOrFail('test-wallet'));
    }

    public function test_has_wallet_get_wallets()
    {
        $wallets = self::$company->getWallets();
        $this->assertCount(0, $wallets);

        self::$company->createWallet('test-wallet', Money::getDefaultCurrency());
        $wallets = self::$company->getWallets('test-wallet');
        $this->assertCount(1, $wallets);

        self::$company->createWallet('test-wallet1', Money::getDefaultCurrency());
        $wallets = self::$company->getWallets();
        $this->assertCount(2, $wallets);
    }

    public function test_has_wallet_create_wallet_and_has_wallet()
    {
        self::$company->createWallet('test-wallet', Money::getDefaultCurrency());
        $this->assertTrue(self::$company->hasWallet('test-wallet'));
    }

    public function test_has_wallet_transactions()
    {
        $createTransaction = app(CreateTransactions::class);
        $wallet = self::$company->createWallet('test-wallet', Money::getDefaultCurrency());

        $this->assertEquals(0, self::$company->transactions('test-wallet')->count());

        $createTransaction->handle($wallet, TransactionReason::DepositByEdaat, new Money(1500), []);

        $transaction = self::$company->transactions('test-wallet');
        $this->assertEquals(1, $transaction->count());
        $this->assertInstanceOf(Transaction::class, $transaction->first());
    }

    public function test_has_wallet_balance()
    {
        $wallet = self::$company->createWallet('test-wallet', Money::getDefaultCurrency());
        $this->assertTrue(self::$company->balance('test-wallet')->isZero());

        $wallet->deposit(new Money(1500), 1, []);
        $this->assertTrue(self::$company->balance('test-wallet')->equals(new Money(1500)));
    }
}
