<?php

namespace Tests\Unit\Jobs\Transaction;

use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Enums\Role;
use App\Enums\TransactionReason;
use App\Enums\WalletNotificationType;
use App\Enums\WalletType;
use App\Jobs\Transaction\CheckWalletNotificaitonJob;
use App\Models\Company;
use App\Models\TieredPricing;
use App\Models\User;
use App\Models\WalletNotification;
use App\Notifications\WalletReachedThreshold;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class CheckWalletNotificaitonJobTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithUser;

    protected static Company $company;

    protected static User $lenderAdmin;

    protected static User $lenderBilling;

    protected static User $lenderSupervisor;

    protected static User $lenderOrderCreator;

    protected static User $lenderApiUser;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();

        self::$lenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$lenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling);
        self::$lenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor);
        self::$lenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);
        self::$lenderApiUser = $this->createLenderUser(self::$company->id, Role::LenderApiUser);

    }

    /**
     * @throws \Exception
     */
    public function test_check_wallet_if_threshold_reached_with_order_count_type()
    {
        Notification::fake();
        $wallet = self::$company->getWallet(WalletType::CompanyWallet);
        $balance = $wallet->balance;

        $orderCostWithVat = TieredPricing::getOrderCostIfStandard(self::$company)['costWithVat'];

        $orderCount = $balance->divide($orderCostWithVat->getAmount(), \Money\Money::ROUND_DOWN);

        WalletNotification::factory([
            'type' => WalletNotificationType::ORDER_COUNT,
            'value' => $orderCount->getAmount() - 1,
        ])
            ->for(self::$company)
            ->for($wallet)
            ->notNotified()
            ->create();

        app(CreateTransactions::class)->handle($wallet, TransactionReason::OrderCreationFee, $orderCostWithVat, []);

        (new CheckWalletNotificaitonJob($wallet))->handle();

        Notification::assertSentTo(self::$lenderAdmin, WalletReachedThreshold::class);
        Notification::assertSentTo(self::$lenderBilling, WalletReachedThreshold::class);
        Notification::assertNotSentTo(self::$lenderSupervisor, WalletReachedThreshold::class);
        Notification::assertNotSentTo(self::$lenderOrderCreator, WalletReachedThreshold::class);
        Notification::assertNotSentTo(self::$lenderApiUser, WalletReachedThreshold::class);
    }

    /**
     * @throws \Exception
     */
    public function test_check_wallet_if_threshold_reached_with_wallet_balance_type()
    {
        Notification::fake();
        $wallet = self::$company->getWallet(WalletType::CompanyWallet);
        $balance = $wallet->balance;
        $amount = Money::parseByDecimal(10, $wallet->currency);

        WalletNotification::factory([
            'type' => WalletNotificationType::WALLET_BALANCE,
            'value' => $balance->subtract($amount),
        ])
            ->for(self::$company)
            ->for($wallet)
            ->notNotified()
            ->create();

        app(CreateTransactions::class)->handle($wallet, TransactionReason::OrderCreationFee, $amount, []);

        (new CheckWalletNotificaitonJob($wallet))->handle();

        Notification::assertSentTo(self::$lenderAdmin, WalletReachedThreshold::class);
        Notification::assertSentTo(self::$lenderBilling, WalletReachedThreshold::class);
        Notification::assertNotSentTo(self::$lenderSupervisor, WalletReachedThreshold::class);
        Notification::assertNotSentTo(self::$lenderOrderCreator, WalletReachedThreshold::class);
        Notification::assertNotSentTo(self::$lenderApiUser, WalletReachedThreshold::class);
    }

    /**
     * @throws \Exception
     */
    public function test_check_wallet_if_threshold_reached_with_notification_already_notified()
    {
        Notification::fake();
        $wallet = self::$company->getWallet(WalletType::CompanyWallet);
        $balance = $wallet->balance;
        $amount = Money::parseByDecimal(10, $wallet->currency);

        WalletNotification::factory()
            ->for(self::$company)
            ->for($wallet)
            ->notified()
            ->create();

        app(CreateTransactions::class)->handle($wallet, TransactionReason::OrderCreationFee, $amount, []);

        (new CheckWalletNotificaitonJob($wallet))->handle();

        Notification::assertNotSentTo(self::$lenderAdmin, WalletReachedThreshold::class);
        Notification::assertNotSentTo(self::$lenderBilling, WalletReachedThreshold::class);
        Notification::assertNotSentTo(self::$lenderSupervisor, WalletReachedThreshold::class);
        Notification::assertNotSentTo(self::$lenderOrderCreator, WalletReachedThreshold::class);
        Notification::assertNotSentTo(self::$lenderApiUser, WalletReachedThreshold::class);
    }
}
