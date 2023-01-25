<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class UpdateLenderStatusTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithUser;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    private static User $userLenderAdmin;

    private static array $companyStatusDetails;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910', 'type' => CompanyType::Trader]);
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createAdminUser(
            perm(Area::SuperAdmin, [Subject::TraderStatus, Action::Edit]),
        );
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id);

        self::$companyStatusDetails = [
            'status' => CompanyStatus::Approved(),
            'public_status_comment' => 'Approved public',
            'internal_status_comment' => 'Approved internal',
        ];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_update_company_status(): void
    {
        $this->putJson('api/v1/admin/companies/traders/'.self::$company->id.'/status', self::$companyStatusDetails)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_can_update_company_status(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/companies/traders/'.self::$company->id.'/status', self::$companyStatusDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        self::$company = self::$company->refresh();

        $this->assertTrue(self::$company->status->is(CompanyStatus::Approved));
        $this->assertEquals('Approved public', self::$company->public_status_comment);
        $this->assertEquals('Approved internal', self::$company->internal_status_comment);
    }

    /**
     * @return void
     */
    public function test_that_manager_can_update_company_status(): void
    {
        $this->actingAs(self::$userManager)
            ->putJson('api/v1/admin/companies/traders/'.self::$company->id.'/status', self::$companyStatusDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        self::$company = self::$company->refresh();

        $this->assertTrue(self::$company->status->is(CompanyStatus::Approved));
        $this->assertEquals('Approved public', self::$company->public_status_comment);
        $this->assertEquals('Approved internal', self::$company->internal_status_comment);
    }

    /**
     * @return void
     */
    public function test_that_manager_without_permissions_cant_update_company_status(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->putJson('api/v1/admin/companies/traders/'.self::$company->id.'/status', self::$companyStatusDetails)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_admin_cant_update_company_status_without_status(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/companies/traders/'.self::$company->id.'/status', Arr::except(self::$companyStatusDetails, 'status'))
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('status');
    }

    /**
     * @return void
     */
    public function test_that_admin_cant_update_company_status_without_public_status_comment(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/companies/'.self::$company->id.'/status', Arr::except(self::$companyStatusDetails, 'public_status_comment'))
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('public_status_comment');
    }

    /**
     * @return void
     */
    public function test_that_admin_cant_update_company_status_without_internal_status_comment(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/companies/'.self::$company->id.'/status', Arr::except(self::$companyStatusDetails, 'internal_status_comment'))
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('internal_status_comment');
    }
}
