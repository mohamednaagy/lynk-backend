<?php

namespace Tests\Unit\Wallets\Traits;

use App\Enums\TransactionReason;
use App\Models\Wallet;
use App\Support\Money\Money;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class CanPayTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Wallet $firstWallet;

    private static Wallet $secondWallet;

    /**
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        [$_, self::$firstWallet] = $this->createCompany(1500);
        [$_, self::$secondWallet] = $this->createCompany(1500, [
            'company_cr' => '12345678911',
        ]);
    }

    public function test_can_pay_withdraw()
    {
        $amount = new Money(500);

        self::$firstWallet->withdraw($amount, TransactionReason::OrderCreationFee, [
            'data' => 'dummy',
        ]);

        self::$firstWallet->withdraw($amount, TransactionReason::OrderCreationFee, '123456');

        self::$firstWallet->withdraw($amount, TransactionReason::OrderCreationFee, '123457', [
            'data' => 'dummy',
        ]);

        $this->assertTrue(self::$firstWallet->balance->isZero());
    }

    public function test_can_pay_deposit()
    {
        $amount = new Money(500);

        self::$firstWallet->deposit($amount, TransactionReason::DepositByEdaat, [
            'data' => 'dummy',
        ]);

        self::$firstWallet->deposit($amount, TransactionReason::DepositByEdaat, '123456');

        self::$firstWallet->deposit($amount, TransactionReason::DepositByEdaat, '123457', [
            'data' => 'dummy',
        ]);

        $this->assertTrue(self::$firstWallet->balance->equals(new Money(3000)));
    }

    public function test_can_pay_transfer()
    {
        $amount = new Money(500);

        self::$firstWallet->transfer(self::$secondWallet, $amount, TransactionReason::ManualDeposit, [
            'data' => 'dummy',
        ]);

        self::$firstWallet->transfer(self::$secondWallet, $amount, TransactionReason::ManualDeposit, '123456');

        self::$firstWallet->transfer(self::$secondWallet, $amount, TransactionReason::ManualDeposit, '123457', [
            'data' => 'dummy',
        ]);

        $this->assertTrue(self::$firstWallet->balance->isZero());
        $this->assertTrue(self::$secondWallet->balance->equals(new Money(3000)));
    }
}
