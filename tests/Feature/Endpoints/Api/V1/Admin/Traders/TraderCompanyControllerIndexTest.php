<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders;

use App\Enums\Area;
use App\Enums\CompanyType;
use App\Models\Company;
use App\Models\User;
use App\Transformers\CompanyTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class TraderCompanyControllerIndexTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static User $superAdmin;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$superAdmin = $this->createSuperAdminUser();
    }

    /**
     * @return void
     */
    public function test_trader_company_controller_index_un_auth_user_cant_index_companies(): void
    {
        $this->getJson('api/v1/admin/traders')
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_trader_company_controller_index()
    {
        $this->actingAs(self::$superAdmin)
            ->getJson('api/v1/admin/traders')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(Company::where('type', CompanyType::Trader)->withCount('orders')->paginate(), new CompanyTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'status',
                        'unique_name',
                        'orders_count',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_trader_company_controller_index_other_roles_can_not_access()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function ($user, $role) {
            return $this->actingAs($user)
                ->getJson('api/v1/admin/traders');
        });
    }
}
