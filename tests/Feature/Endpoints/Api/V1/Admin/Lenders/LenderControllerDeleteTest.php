<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithLender;

class LenderControllerDeleteTest extends TestCase
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
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Delete]),
        );
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_delete_lenders(): void
    {
        $this->deleteJson('api/v1/admin/lenders/'.self::$lender->id)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_admin_user_can_delete_lenders(): void
    {
        $lendersCount = Company::query()->count();

        $this->actingAs(self::$userAdmin)
            ->deleteJson('api/v1/admin/lenders/'.self::$lender->id)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $newLendersCount = Company::query()->count();

        $this->assertEquals($newLendersCount, $lendersCount - 1);
    }

    /**
     * @return void
     */
    public function test_that_auth_manager_user_can_delete_lenders(): void
    {
        $lendersCount = Company::query()->count();

        $this->actingAs(self::$userManager)
            ->deleteJson('api/v1/admin/lenders/'.self::$lender->id)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $newLendersCount = Company::query()->count();

        $this->assertEquals($newLendersCount, $lendersCount - 1);
    }

    /**
     * @return void
     */
    public function test_that_auth_manager_user_without_permissions_cant_delete_lenders(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->deleteJson('api/v1/admin/lenders/'.self::$lender->id)
            ->assertForbidden();
    }
}
