<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Users;

use App\Enums\CompanyStatus;
use App\Enums\ErrorCode;
use App\Enums\Role;
use App\Mail\CompleteRegisterInvitation;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ResendInvitationTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;

    private static Company $company;

    private static Company $companyNotActive;

    private static User $lender;

    private static User $lenderBelongsToCompanyNotActive;

    private static User $LenderSupervisor;

    private static User $LenderBilling;

    private static User $lenderApiUser;

    private static User $lenderCrearor;

    private static User $lenerAdmin;

    private static User $lenerAdminBelonsToCompanyNotActive;

    private static User $lenerAdminNotVerified;

    private static string $redirectUrl;

    public function setUp(): void
    {
        parent::setUp();

        self::$redirectUrl = 'http://localhost';
        self::$company = $this->createCompany('2000')[0];
        self::$companyNotActive = $this->createCompany('2000', [
            'status' => CompanyStatus::Pending,
            'company_cr' => '12345678999',
        ])[0];

        self::$LenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor, 'LenderSupervisor@bim.com');
        self::$LenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling, 'LenderBilling@bim.com');
        self::$lenerAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'LenderAdmin@bim.com');
        self::$lenderApiUser = $this->createLenderUser(self::$company->id, Role::LenderApiUser, 'LenderAdmin1@bim.com');
        self::$lenderCrearor = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator, 'LenderAdmin1@bim.com');
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'LenderAdmin1@bim.com', ['password' => null]);
        self::$lenerAdminBelonsToCompanyNotActive = $this->createLenderUser(self::$companyNotActive->id, Role::LenderAdmin, 'LenderAdmin2@bim.com');

        self::$lenerAdminNotVerified = $this->createLenderUser(
            self::$company->id,
            Role::LenderAdmin,
            'LenderAdmin@bim.com',
            ['email_verified_at' => null]
        );

        self::$lenderBelongsToCompanyNotActive = $this->createLenderUser(
            self::$companyNotActive->id,
            Role::LenderAdmin,
            'user@bim.com'
        );
    }

    public function test_resend_invitation_can_not_access_when_company_not_active()
    {
        $this->withHeader('X-Company', self::$companyNotActive->id)
            ->actingAs(self::$lenerAdminBelonsToCompanyNotActive)
            ->postJson(
                'api/v1/lender/users/'.self::$lenderBelongsToCompanyNotActive->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(403)->assertJsonFragment([
                'message' => __('error.company_not_active'),
                'code' => ErrorCode::COMPANY_NOT_ACTIVE,
            ]);
    }

    public function test_resend_invitation_can_not_access_without_verify_email()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenerAdminNotVerified)
            ->postJson(
                'api/v1/lender/users/'.self::$lender->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(403)
            ->assertJsonFragment([
                'message' => __('error.must_verify_email'),
                'code' => ErrorCode::EMAIL_NOT_VERIFIED,
            ]);
    }

    public function test_resend_invitation_lender_supervisor_can_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$LenderSupervisor)
            ->postJson(
                'api/v1/lender/users/'.self::$lender->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);
    }

    public function test_resend_invitation_lender_admin_can_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenerAdmin)
            ->postJson(
                'api/v1/lender/users/'.self::$lender->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);
    }

    public function test_resend_invitation_lender_billing_can_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$LenderBilling)
            ->postJson(
                'api/v1/lender/users/'.self::$lender->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);
    }

    public function test_resend_invitation_lender_api_user_can_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenderApiUser)
            ->postJson(
                'api/v1/lender/users/'.self::$lender->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);
    }

    public function test_resend_invitation_lender_order_creator_can_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenderCrearor)
            ->postJson(
                'api/v1/lender/users/'.self::$lender->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);
    }

    public function test_resend_invitation_email_is_sent()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenerAdmin)
            ->postJson(
                'api/v1/lender/users/'.self::$lender->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);

        Mail::fake();
        Mail::send(new CompleteRegisterInvitation(self::$lender, self::$redirectUrl));
        Mail::assertSent(CompleteRegisterInvitation::class, function ($mail) {
            $this->assertInstanceOf(User::class, $mail->user);
            $this->assertTrue($mail->user->id == self::$lender->id);

            return true;
        });
    }
}
