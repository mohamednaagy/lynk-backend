<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\CompanyTransformer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithLender;

class LenderControllerShowTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender, InteractsWithAdmin;

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

        [self::$lender, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userAdmin = $this->createAdmin('admin@bim.com');
        self::$userManager = $this->createManager(
            'manager@bim.com',
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Show]),
        );
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_show_lender(): void
    {
        $this->getJson('api/v1/admin/lenders/'.self::$lender->id)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_admin_user_can_show_lender(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$lender, new CompanyTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'status',
                        'created_at',
                        'unique_name',
                        'company_cr',
                        'does_order_require_approval',
                        'order_cost',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_manager_user_can_show_lender(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id)
            ->assertOk()
            ->assertExactJson(
                fractal(self::$lender, new CompanyTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'status',
                        'created_at',
                        'unique_name',
                        'company_cr',
                        'does_order_require_approval',
                        'order_cost',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_manager_user_without_permissions_cant_show_lender(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->getJson('api/v1/admin/lenders/'.self::$lender->id)
            ->assertForbidden();
    }
}
