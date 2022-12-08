<?php

namespace Endpoints\Api\V1\Lender\Wallets;

use App\Enums\Role;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionServiceInterface;
use App\Transformers\TransactionTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class GetWalletTransactionsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;

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
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
    }

    public function test_can_lender_admin_get_wallet_transaction_successfully()
    {
        $this->actingAs(self::$userLender)
            ->getJson('/api/v1/lender/wallet/transactions', ['X-Company' => self::$company->id])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertExactJson(
                json_decode(fractal(
                    self::$company->transactions(WalletType::CompanyWallet)->paginate(), new TransactionTransformer()
                )->toJson(), true)
            );

        app()->make(TransactionServiceInterface::class)->deposit(
            self::$wallet, \money(20000, 'SAR'), TransactionReason::DepositByEdaat, 2, []
        );

        $this->actingAs(self::$userLender)
            ->getJson('/api/v1/lender/wallet/transactions', ['X-Company' => self::$company->id])
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertExactJson(
                json_decode(fractal(
                    self::$company->transactions(WalletType::CompanyWallet)->paginate(), new TransactionTransformer()
                )->toJson(), true)
            );
    }

    public function test_can_lender_supervisor_get_wallet_transaction_successfully()
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderSupervisor);

        $this->actingAs(self::$userLender)
            ->getJson('/api/v1/lender/wallet/transactions', ['X-Company' => self::$company->id])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertExactJson(
                json_decode(fractal(
                    self::$company->transactions(WalletType::CompanyWallet)->paginate(), new TransactionTransformer()
                )->toJson(), true)
            );
    }

    public function test_can_lender_billing_get_wallet_transaction_successfully()
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderBilling);

        $this->actingAs(self::$userLender)
            ->getJson('/api/v1/lender/wallet/transactions', ['X-Company' => self::$company->id])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertExactJson(
                json_decode(fractal(
                    self::$company->transactions(WalletType::CompanyWallet)->paginate(), new TransactionTransformer()
                )->toJson(), true)
            );
    }

    public function test_can_lender_api_get_wallet_transaction_successfully()
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderApiUser);

        $this->actingAs(self::$userLender)
            ->getJson('/api/v1/lender/wallet/transactions', ['X-Company' => self::$company->id])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertExactJson(
                json_decode(fractal(
                    self::$company->transactions(WalletType::CompanyWallet)->paginate(), new TransactionTransformer()
                )->toJson(), true)
            );
    }

    public function test_can_lender_creator_get_wallet_transaction_successfully()
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderOrderCreator);

        $this->actingAs(self::$userLender)
            ->getJson('/api/v1/lender/wallet/transactions', ['X-Company' => self::$company->id])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
