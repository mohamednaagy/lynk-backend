<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Modules\Grantify\Facades\Grantify;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class UpdateTraderStatusTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany, AssertsAccessByRoleAndArea;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    private static User $userTraderAdmin;

    private static array $companyStatusDetails;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createTraderCompany('2000', ['company_cr' => '12345678910', 'status' => CompanyStatus::Pending]);
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$userManager, perm(Area::SuperAdmin, [Subject::TraderStatus, Action::Edit]));
        self::$userTraderAdmin = $this->createTraderUser(self::$company->id, Role::TraderAdmin);
        self::$companyStatusDetails = [
            'status' => CompanyStatus::Approved(),
            'public_status_comment' => 'public_status_comment',
            'internal_status_comment' => 'internal_status_comment',
        ];
    }

    /**
     * @return void
     */
    public function test_un_auth_user_cant_update_company_status_will_fail(): void
    {
        $this->putJson(
            'api/v1/admin/traders/'.self::$company->id.'/status',
            self::$companyStatusDetails
        )->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_user_cant_update_company_status_with_invalid_roles_will_fail(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(401, [Area::SuperAdmin], function () {
            return $this->putJson(
                'api/v1/admin/traders/'.self::$company->id.'/status',
                self::$companyStatusDetails
            )->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
        });
    }

    /**
     * @return void
     */
    public function test_admin_can_update_company_status_will_success(): void
    {
        $activityLogCount = Activity::query()->count();

        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/traders/'.self::$company->id.'/status', self::$companyStatusDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        self::$company = self::$company->refresh();

        $this->assertTrue(self::$company->status->is(CompanyStatus::Approved));
        $this->assertEquals('public_status_comment', self::$company->public_status_comment);
        $this->assertEquals('internal_status_comment', self::$company->internal_status_comment);
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     */
    public function test_manager_can_update_company_status_will_success(): void
    {
        $activityLogCount = Activity::query()->count();

        $this->actingAs(self::$userManager)
            ->putJson('api/v1/admin/traders/'.self::$company->id.'/status', self::$companyStatusDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        self::$company = self::$company->refresh();

        $this->assertTrue(self::$company->status->is(CompanyStatus::Approved));
        $this->assertEquals('public_status_comment', self::$company->public_status_comment);
        $this->assertEquals('internal_status_comment', self::$company->internal_status_comment);
        $this->assertDatabaseCount((new Activity())->getTable(), $activityLogCount + 1);
    }

    /**
     * @return void
     */
    public function test_manager_without_permissions_cant_update_company_status_will_fail(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->putJson('api/v1/admin/traders/'.self::$company->id.'/status', self::$companyStatusDetails)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_admin_cant_update_company_status_without_status_will_fail(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/traders/'.self::$company->id.'/status', Arr::except(self::$companyStatusDetails, 'status'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The status field is required.',
                'errors' => [
                    'status' => [
                        'The status field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_admin_cant_update_company_status_without_public_status_comment_will_fail(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/traders/'.self::$company->id.'/status', Arr::except(self::$companyStatusDetails, 'public_status_comment'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The public status comment field is required.',
                'errors' => [
                    'public_status_comment' => [
                        'The public status comment field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_admin_cant_update_company_status_without_internal_status_comment_will_fail(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/traders/'.self::$company->id.'/status', Arr::except(self::$companyStatusDetails, 'internal_status_comment'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The internal status comment field is required.',
                'errors' => [
                    'internal_status_comment' => [
                        'The internal status comment field is required.',
                    ],
                ],
            ]);
    }
}
