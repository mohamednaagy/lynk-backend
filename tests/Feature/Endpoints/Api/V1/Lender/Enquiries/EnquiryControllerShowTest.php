<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Enquiries;

use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\Enquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class EnquiryControllerShowTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;

    private static Company $company;

    private static Company $companyUnderReview;

    private static Company $pendingCompany;

    private static User $userLenderAdmin;

    private static User $userBilling;

    private static User $userWithoutEmailVerification;

    private static User $userLenderAdminBelongsToCompanyUnderReview;

    private static User $userLenderAdminBelongsToPendingCompany;

    private static Enquiry $enquiry;

    private static Enquiry $anotherEnquiry;

    private static Enquiry $enquiryBelongsToPendingCompany;

    private static Enquiry $enquiryBelongsToUnderRevirwCompany;

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

        self::$userWithoutEmailVerification = $this->createLenderUser(
            self::$company->id,
            Role::LenderAdmin,
            'LenderAdmin2@bim.com',
            [
                'email_verified_at' => null,
            ]
        );

        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'LenderAdmin@bim.com');
        self::$userBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling, 'LenderBilling@bim.com');
        self::$userLenderAdminBelongsToCompanyUnderReview = $this->createLenderUser(self::$companyUnderReview->id, Role::LenderAdmin, 'LenderAdmin3@bim.com');
        self::$userLenderAdminBelongsToPendingCompany = $this->createLenderUser(self::$companyUnderReview->id, Role::LenderAdmin, 'LenderAdmin4@bim.com');

        self::$enquiry = Enquiry::factory()->create(['user_id' => self::$userLenderAdmin->id]);
        self::$anotherEnquiry = Enquiry::factory()->create(['user_id' => self::$userWithoutEmailVerification->id]);
        self::$enquiryBelongsToPendingCompany = Enquiry::factory()->create(['user_id' => self::$userLenderAdminBelongsToPendingCompany->id]);
        self::$enquiryBelongsToUnderRevirwCompany = Enquiry::factory()->create(['user_id' => self::$userLenderAdminBelongsToCompanyUnderReview->id]);
    }

    public function test_enquiry_controller_show_successed()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/enquiries/'.self::$enquiry->id)
            ->assertStatus(200);
    }

    public function test_enquiry_controller_show_user_only_can_see_his_enquiry()
    {
        $this->actingAs(self::$userBilling)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/enquiries/'.self::$anotherEnquiry->id)
            ->assertStatus(403);
    }

    public function test_enquiry_controller_show_all_lender_roles_can_access_except_api_user()
    {
        $rolesHasAccess = [Role::LenderAdmin, Role::LenderSupervisor, Role::LenderBilling, Role::LenderOrderCreator];

        $this->assertStatusToSpecificRoles(200, $rolesHasAccess, null, function ($user, $role) {
            $enquiry = Enquiry::factory()->create(['user_id' => $user->id]);

            return $this->actingAs($user)
                ->withHeader('X-Company', $user->company_id)
                ->getJson('api/v1/lender/enquiries/'.$enquiry->id);
        });
    }

    public function test_enquiry_controller_show_lender_api_user_can_not_access()
    {
        $rolesHasNoPermission = [Role::LenderApiUser];

        $this->assertStatusToSpecificRoles(403, $rolesHasNoPermission, null, function ($user, $role) {
            $enquiry = Enquiry::factory()->create(['user_id' => $user->id]);

            return $this->actingAs($user)
                ->withHeader('X-Company', $user->company_id)
                ->getJson('api/v1/lender/enquiries/'.$enquiry->id);
        });
    }

    public function test_enquiry_controller_show_user_access_without_email_verificaion()
    {
        $this->actingAs(self::$userWithoutEmailVerification)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/enquiries/'.self::$anotherEnquiry->id)
            ->assertStatus(200);
    }

    public function test_enquiry_controller_show_user_can_access_with_company_has_under_review_status()
    {
        $this->actingAs(self::$userLenderAdminBelongsToCompanyUnderReview)
            ->withHeader('X-Company', self::$companyUnderReview->id)
            ->getJson('api/v1/lender/enquiries/'.self::$enquiryBelongsToUnderRevirwCompany->id)
            ->assertStatus(200);
    }

    public function test_enquiry_controller_show_user_can_access_with_company_has_pending_status()
    {
        $this->actingAs(self::$userLenderAdminBelongsToPendingCompany)
            ->withHeader('X-Company', self::$pendingCompany->id)
            ->getJson('api/v1/lender/enquiries/'.self::$enquiryBelongsToPendingCompany->id)
            ->assertStatus(200);
    }
}
