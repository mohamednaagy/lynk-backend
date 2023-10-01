<?php

namespace Tests\Unit\Jobs\Transaction;

use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Enums\TransactionReason;
use App\Enums\WalletNotificationType;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\WalletNotification;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;

class CheckWalletNotificaitonJobTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany;

    protected static Company $lender;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$lender] = $this->createCompany();
    }

    public function test_check_wallet_if_threshold_reached_with_order_count_type()
    {
        $wallet = self::$lender->getWallet(WalletType::CompanyWallet);
        WalletNotification::factory([
            'type' => WalletNotificationType::ORDER_COUNT,
            'value' => Money::parse(2000, null, false)->getAmount(),
        ])
            ->for(self::$lender)
            ->for($wallet)->create();

        app(CreateTransactions::class)->handle($wallet, TransactionReason::OrderCreationFee, $amount, []);

    }
}
