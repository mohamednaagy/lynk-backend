<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\TransactionTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class LenderOrderTransactionControllerTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $lender;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$lender, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910', 'order_cost' => '200']);
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$userManager, perm(Area::SuperAdmin, [Subject::LenderTransactions, Action::Index]));
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_get_lender_transactions(): void
    {
        $this->getJson('api/v1/admin/lenders/'.self::$lender->id.'/transactions')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_can_get_lender_transactions(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id.'/transactions')
            ->assertOk()
            ->assertExactJson(
                fractal(
                    self::$lender->transactions(WalletType::CompanyWallet)->paginate(),
                    new TransactionTransformer()
                )->parseIncludes([
                    'id',
                    'date',
                    'description',
                    'amount',
                    'receipt_url',
                ])->respond()->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_manager_can_get_lender_transactions(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id.'/transactions')
            ->assertOk()
            ->assertExactJson(
                fractal(
                    self::$lender->transactions(WalletType::CompanyWallet)->paginate(),
                    new TransactionTransformer()
                )->parseIncludes([
                    'id',
                    'date',
                    'description',
                    'amount',
                    'receipt_url',
                ])->respond()->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_manager_without_permissions_cant_get_lender_transactions(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id.'/transactions')
            ->assertForbidden();
    }
}
