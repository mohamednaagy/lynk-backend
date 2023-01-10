<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Trader\Companies;

use App\Enums\Area;
use App\Enums\CompanyType;
use App\Models\Company;
use App\Models\User;
use App\Transformers\CompanyTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;

class TraderCompanyControllerShowTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithCompany;

    private static User $trader;

    private static array $companyDetails;

    private static Company $company;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$trader = $this->createTraderUser();

        [self::$company] = $this->createCompany(2000, [
            'type' => CompanyType::Trader,
        ]);

        self::$companyDetails = [
            'name' => 'testCompany',
            'unique_name' => 'companyUniqueName',
            'company_cr' => '1234567891',
            'driver' => 'dmcc',
        ];
    }

    /**
     * @return void
     */
    public function test_trader_company_controller_show_un_auth_user_cant_show_company(): void
    {
        $this->getJson('api/v1/admin/traders/companies/'.self::$company->id)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_trader_company_controller_show_other_roles_can_not_access()
    {
        $this->asserStatusForAllRoleExceptGivingAreaRoles(403, Area::Trader, function ($user, $role) {
            return $this->actingAs($user)
                ->getJson('api/v1/admin/traders/companies/'.self::$company->id);
        });
    }

    public function test_trader_company_controller_show_successful()
    {
        $this->actingAs(self::$trader)
            ->getJson('api/v1/admin/traders/companies/'.self::$company->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$company, new CompanyTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'unique_name',
                        'driver',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }
}
