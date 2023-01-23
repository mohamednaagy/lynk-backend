<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Enquiries;

use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\Enquiry;
use App\Models\User;
use App\Transformers\EnquiryTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class EnquiryControllerIndexTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static Company $company;

    private static Company $companyUnderReview;

    private static Company $pendingCompany;

    private static User $userLenderAdmin;

    private static User $userBilling;

    private static User $userWithoutEmailVerification;

    private static User $userLenderAdminBelongsToCompanyUnderReview;

    private static User $userLenderAdminBelongsToPendingCompany;

    private static User $userHasNoEnuiry;

    private static Enquiry $enquiry;

    private static Enquiry $anotherEnquiry;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        [self::$companyUnderReview] = $this->createCompany(
            '2000',
            [
                'company_cr' => '1234567999',
                'status' => CompanyStatus::UnderReview,
            ]
        );

        [self::$pendingCompany] = $this->createCompany(
            '2000',
            [
                'company_cr' => '1234567889',
                'status' => CompanyStatus::Pending,
            ]
        );

        self::$userWithoutEmailVerification = $this->createLenderUser(
            self::$company->id,
            Role::LenderAdmin,
            [
                'email_verified_at' => null,
            ]
        );

        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$userBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling);
        self::$userHasNoEnuiry = $this->createLenderUser(self::$company->id, Role::LenderBilling);
        self::$userLenderAdminBelongsToCompanyUnderReview = $this->createLenderUser(self::$companyUnderReview->id, Role::LenderAdmin);
        self::$userLenderAdminBelongsToPendingCompany = $this->createLenderUser(self::$companyUnderReview->id, Role::LenderAdmin);

        self::$enquiry = Enquiry::factory()->create(['user_id' => self::$userLenderAdmin->id]);
        self::$anotherEnquiry = Enquiry::factory()->create(['user_id' => self::$userWithoutEmailVerification->id]);
    }

    public function test_enquiry_controller_index_successed()
    {
        $enquiries = Enquiry::where('user_id', self::$userLenderAdmin->id)
            ->paginate();

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/enquiries')
            ->assertExactJson(
                fractal($enquiries, new EnquiryTransformer())
                    ->parseIncludes([
                        'id',
                        'subject',
                        'status',
                        'creation_date',
                    ])
                    ->respond()->getData(true)
            );
    }

    public function test_enquiry_controller_index_user_only_can_see_his_enquiry()
    {
        $this->actingAs(self::$userHasNoEnuiry)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/enquiries')
            ->assertJsonFragment(
                [
                    'data' => [],
                    'meta' => [
                        'pagination' => [
                            'total' => 0,
                            'count' => 0,
                            'per_page' => 15,
                            'current_page' => 1,
                            'total_pages' => 1,
                            'links' => [],
                        ],
                    ],
                ]
            );
    }

    public function test_enquiry_controller_index_all_lender_roles_can_access_except_api_user()
    {
        $rolesHasAccess = [Role::LenderAdmin, Role::LenderSupervisor, Role::LenderBilling, Role::LenderOrderCreator];

        $this->assertStatusCodeToSpecificRoles(200, $rolesHasAccess, function ($user, $role) {
            return $this->actingAs($user)
                ->withHeader('X-Company', $user->company_id)
                ->getJson('api/v1/lender/enquiries');
        });
    }

    public function test_enquiry_controller_index_lender_api_user_can_not_access()
    {
        $rolesHasNoPermission = [Role::LenderApiUser];

        $this->assertStatusCodeToSpecificRoles(403, $rolesHasNoPermission, function ($user, $role) {
            return $this->actingAs($user)
                ->withHeader('X-Company', $user->company_id)
                ->getJson('api/v1/lender/enquiries');
        });
    }

    public function test_enquiry_controller_index_user_access_without_email_verificaion()
    {
        $this->actingAs(self::$userWithoutEmailVerification)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/enquiries')
            ->assertStatus(200);
    }

    public function test_enquiry_controller_index_user_can_access_with_company_has_under_review_status()
    {
        $this->actingAs(self::$userLenderAdminBelongsToCompanyUnderReview)
            ->withHeader('X-Company', self::$companyUnderReview->id)
            ->getJson('api/v1/lender/enquiries')
            ->assertStatus(200);
    }

    public function test_enquiry_controller_index_user_can_access_with_company_has_pending_status()
    {
        $this->actingAs(self::$userLenderAdminBelongsToPendingCompany)
            ->withHeader('X-Company', self::$pendingCompany->id)
            ->getJson('api/v1/lender/enquiries')
            ->assertStatus(200);
    }
}
