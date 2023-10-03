<?php

namespace Tests\Unit\Wallets\Transactions;

use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Jobs\Transaction\CheckWalletNotificaitonJob;
use App\Models\Company;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;

/**
 * @see \App\Observers\TransactionObserver
 */
class TransactionObserverTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany;

    protected static Company $lender;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$lender] = $this->createCompany();
    }

    public function test_fire_job_to_check_balance_threshold_reached_when_negative_transaction_is_created()
    {
        Queue::fake([
            CheckWalletNotificaitonJob::class,
        ]);
        $wallet = self::$lender->getWallet(WalletType::CompanyWallet);
        $amount = Money::parseByDecimal(10000, $wallet->currency);

        app(CreateTransactions::class)->handle($wallet, TransactionReason::OrderCreationFee, $amount, []);

        Queue::assertPushed(CheckWalletNotificaitonJob::class);
    }

    public function test_mot_fire_job_to_check_balance_threshold_reached_when_positive_transaction_is_created()
    {
        Queue::fake([
            CheckWalletNotificaitonJob::class,
        ]);
        $wallet = self::$lender->getWallet(WalletType::CompanyWallet);
        $amount = Money::parseByDecimal(10000, $wallet->currency);

        app(CreateTransactions::class)->handle($wallet, TransactionReason::ManualDeposit, $amount, []);

        Queue::assertNotPushed(CheckWalletNotificaitonJob::class);
    }
}
