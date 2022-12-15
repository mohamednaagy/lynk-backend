<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Enquiries;

use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class EnquiryControllerStoreTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;

    private static Company $company;

    private static Company $companyUnderReview;

    private static Company $pendingCompany;

    private static User $userLenderAdmin;

    private static User $userWithoutEmailVerification;

    private static User $userLenderAdminBelongsToCompanyUnderReview;

    private static User $userLenderAdminBelongsToPendingCompany;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000');
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

        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'LenderAdmin@bim.com');
        self::$userLenderAdminBelongsToCompanyUnderReview = $this->createLenderUser(self::$companyUnderReview->id, Role::LenderAdmin, 'LenderAdmin3@bim.com');
        self::$userLenderAdminBelongsToPendingCompany = $this->createLenderUser(self::$companyUnderReview->id, Role::LenderAdmin, 'LenderAdmin4@bim.com');
        self::$userWithoutEmailVerification = $this->createLenderUser(
            self::$company->id,
            Role::LenderAdmin,
            'LenderAdmin2@bim.com',
            [
                'email_verified_at' => null,
            ]
        );
    }

    public function test_enquiry_controller_store_validation_rules()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/enquiries')
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'The subject field is required. (and 1 more error)',
                'subject' => [
                    0 => 'The subject field is required.',
                ],
                'body' => [
                    0 => 'The body field is required.',
                ],
            ]);
    }

    public function test_enquiry_controller_store_sccessed()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(
                'api/v1/lender/enquiries',
                [
                    'subject' => 'testing',
                    'body' => 'body of testing',
                ]
            )
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'subject',
                    'status' => [
                        'description',
                        'value',
                    ],
                    'creation_date',
                ],
            ]);
    }

    public function test_enquiry_controller_store_user_can_create_enquiry_without_email_verification()
    {
        $this->actingAs(self::$userWithoutEmailVerification)
            ->withHeader('X-Company', self::$company->id)
            ->postJson(
                'api/v1/lender/enquiries',
                [
                    'subject' => 'testing',
                    'body' => 'body of testing',
                ]
            )
            ->assertStatus(200);
    }

    public function test_enquiry_controller_store_user_can_access_with_company_has_under_review_status()
    {
        $this->actingAs(self::$userLenderAdminBelongsToCompanyUnderReview)
            ->withHeader('X-Company', self::$companyUnderReview->id)
            ->postJson(
                'api/v1/lender/enquiries',
                [
                    'subject' => 'testing',
                    'body' => 'body of testing',
                ]
            )
            ->assertStatus(200);
    }

    public function test_enquiry_controller_store_user_can_access_with_company_has_pending_status()
    {
        $this->actingAs(self::$userLenderAdminBelongsToPendingCompany)
            ->withHeader('X-Company', self::$pendingCompany->id)
            ->postJson(
                'api/v1/lender/enquiries',
                [
                    'subject' => 'testing',
                    'body' => 'body of testing',
                ]
            )
            ->assertStatus(200);
    }

    public function test_enquiry_controller_store_all_lender_roles_can_access_except_api_user()
    {
        $rolesHasAccess = [Role::LenderAdmin, Role::LenderSupervisor, Role::LenderBilling, Role::LenderOrderCreator];

        $this->assertStatusToSpecificRoles(200, $rolesHasAccess, self::$company, function ($user, $role) {
            return $this->actingAs($user)
                ->withHeader('X-Company', self::$company->id)
                ->postJson(
                    'api/v1/lender/enquiries',
                    [
                        'subject' => 'testing',
                        'body' => 'body of testing',
                    ]
                );
        });
    }

    public function test_enquiry_controller_store_lender_api_user_can_not_access()
    {
        $rolesHasNoPermission = [Role::LenderApiUser];

        $this->assertStatusToSpecificRoles(403, $rolesHasNoPermission, self::$company, function ($user, $role) {
            return $this->actingAs($user)
                ->withHeader('X-Company', self::$company->id)
                ->postJson(
                    'api/v1/lender/enquiries',
                    [
                        'subject' => 'testing',
                        'body' => 'body of testing',
                    ]
                );
        });
    }
}
