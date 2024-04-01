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
use Carbon\Carbon;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class LenderOrderTransactionControllerTest extends TestCase
{
    use InteractsWithCompany, InteractsWithUser, RefreshDatabase;

    private static Company $lender;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    private static string $endpoint;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$lender = $this->createLenderCompanyWithStandardOrderCost('2000', ['company_cr' => '12345678910']);
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::SuperAdmin, [Subject::LenderTransactions, Action::Index])
        );

        self::$endpoint = 'api/v1/admin/lenders/';
    }

    public function test_un_auth_user_cant_get_lender_transactions(): void
    {
        $this->getJson(self::$endpoint.self::$lender->id.'/transactions')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_admin_can_get_lender_transactions_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.self::$lender->id.'/transactions')
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
                    'amount_formatted',
                    'receipt_url',
                ])->respond()->getData(true)
            );
    }

    public function test_admin_can_get_lender_transactions_with_filter_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.self::$lender->id.'/transactions?amount_gte=2000')
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
                    'amount_formatted',
                    'receipt_url',
                ])->respond()->getData(true)
            );

        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.self::$lender->id.'/transactions?amount_gte=2001')
            ->assertOk()
            ->assertExactJson(
                ['data' => [],
                    'meta' => [
                        'pagination' => [
                            'count' => 0,
                            'current_page' => 1,
                            'links' => [],
                            'per_page' => 15,
                            'total' => 0,
                            'total_pages' => 1,
                        ],
                    ],
                ]
            );

        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.self::$lender->id.'/transactions?amount_lte=2000')
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
                    'amount_formatted',
                    'receipt_url',
                ])->respond()->getData(true)
            );

        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.self::$lender->id.'/transactions?amount_lte=1999')
            ->assertOk()
            ->assertExactJson(
                ['data' => [],
                    'meta' => [
                        'pagination' => [
                            'count' => 0,
                            'current_page' => 1,
                            'links' => [],
                            'per_page' => 15,
                            'total' => 0,
                            'total_pages' => 1,
                        ],
                    ],
                ]
            );

        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.self::$lender->id.'/transactions?date_from='.Carbon::now()->toDateString().'&date_to='.Carbon::now()->toDateString())
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
                    'amount_formatted',
                    'receipt_url',
                ])->respond()->getData(true)
            );

    }

    public function test_manager_with_permissions_can_get_lender_transactions_successfully(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson(self::$endpoint.self::$lender->id.'/transactions')
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
                    'amount_formatted',
                    'receipt_url',
                ])->respond()->getData(true)
            );
    }

    public function test_manager_without_permissions_cant_get_lender_transactions(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->getJson(self::$endpoint.self::$lender->id.'/transactions')
            ->assertForbidden();
    }
}
