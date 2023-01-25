<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders;

use App\Enums\Area;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class TraderCompanyControllerShowTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static User $superAdmin;

    private static Company $company;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createTraderCompany(2000);

        self::$superAdmin = $this->createSuperAdminUser();
    }

    /**
     * @return void
     */
    public function test_trader_company_controller_show_un_auth_user_cant_show_company(): void
    {
        $this->getJson('api/v1/admin/traders/'.self::$company->id)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_trader_company_controller_show_successful()
    {
        $this->actingAs(self::$superAdmin)
            ->getJson('api/v1/admin/traders/'.self::$company->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertOk();
    }

    public function test_trader_company_controller_show_other_roles_can_not_access()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->getJson('api/v1/admin/traders/'.self::$company->id);
        });
    }
}
