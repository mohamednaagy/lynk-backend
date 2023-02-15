<?php

namespace Endpoints\Api\V1\Lender\Wallet;

use App\Enums\Role;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Support\Money\Money;
use App\Support\Wallets\Contracts\TransactionServiceInterface;
use App\Transformers\TransactionTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class GetWalletTransactionsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithUser;
    use InteractsWithCompany;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userLender;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('5000');
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
    }

    public function test_get_wallet_transaction_successfully_with_lender_admin()
    {
        $this->actingAs(self::$userLender)
            ->getJson('/api/v1/lender/wallet/transactions', ['X-Company' => self::$company->id])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertExactJson(
                fractal(
                    self::$company->fresh()->transactions(WalletType::CompanyWallet)->paginate(), new TransactionTransformer())
                    ->parseIncludes([
                        'id',
                        'date',
                        'description',
                        'amount',
                        'receipt_url',
                    ])
                    ->respond()
                    ->getData(true)
            );

        app()->make(TransactionServiceInterface::class)->deposit(
            self::$wallet, new Money(20000, 'SAR'), TransactionReason::DepositByEdaat, 2, []
        );

        $this->actingAs(self::$userLender)
            ->getJson('/api/v1/lender/wallet/transactions', ['X-Company' => self::$company->id])
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertExactJson(
                fractal(
                    self::$company->transactions(WalletType::CompanyWallet)->paginate(), new TransactionTransformer())
                    ->parseIncludes([
                        'id',
                        'date',
                        'description',
                        'amount',
                        'receipt_url',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_get_wallet_transaction_successfully_with_lender_supervisor()
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderSupervisor);

        $this->actingAs(self::$userLender)
            ->getJson('/api/v1/lender/wallet/transactions', ['X-Company' => self::$company->id])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertExactJson(
                fractal(
                    self::$company->transactions(WalletType::CompanyWallet)->paginate(), new TransactionTransformer())
                    ->parseIncludes([
                        'id',
                        'date',
                        'description',
                        'amount',
                        'receipt_url',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_get_wallet_transaction_successfully_with_lender_billing()
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderBilling);

        $this->actingAs(self::$userLender)
            ->getJson('/api/v1/lender/wallet/transactions', ['X-Company' => self::$company->id])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertExactJson(
                fractal(
                    self::$company->transactions(WalletType::CompanyWallet)->paginate(), new TransactionTransformer())
                    ->parseIncludes([
                        'id',
                        'date',
                        'description',
                        'amount',
                        'receipt_url',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_get_wallet_transaction_successfully_with_lender_api()
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderApiUser);

        $this->actingAs(self::$userLender)
            ->getJson('/api/v1/lender/wallet/transactions', ['X-Company' => self::$company->id])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertExactJson(
                fractal(
                    self::$company->transactions(WalletType::CompanyWallet)->paginate(), new TransactionTransformer())
                    ->parseIncludes([
                        'id',
                        'date',
                        'description',
                        'amount',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_get_wallet_transaction_unsuccessfully_with_lender_creator()
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderOrderCreator);

        $this->actingAs(self::$userLender)
            ->getJson('/api/v1/lender/wallet/transactions', ['X-Company' => self::$company->id])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
