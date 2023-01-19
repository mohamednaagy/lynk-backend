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
use Tests\Traits\AssertsAccessByRoleAndArea;

class ResendInvitationTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static Company $company;

    private static Company $companyNotActive;

    private static User $lenderAdminNotJoined;

    private static User $lenderBelongsToCompanyNotActive;

    private static User $lenerAdmin;

    private static User $lenerAdminBelonsToCompanyNotActive;

    private static User $lenerAdminNotVerified;

    private static string $redirectUrl;

    public function setUp(): void
    {
        parent::setUp();

        self::$redirectUrl = 'http://localhost';
        [self::$company] = $this->createCompany('2000');
        [self::$companyNotActive] = $this->createCompany(
            '2000',
            [
                'status' => CompanyStatus::Pending,
                'company_cr' => '12345678999',
            ]
        );

        self::$lenerAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$lenderAdminNotJoined = $this->createLenderUser(self::$company->id, Role::LenderAdmin, ['password' => null]);
        self::$lenerAdminBelonsToCompanyNotActive = $this->createLenderUser(self::$companyNotActive->id, Role::LenderAdmin);

        self::$lenerAdminNotVerified = $this->createLenderUser(
            self::$company->id,
            Role::LenderAdmin,
            ['email_verified_at' => null]
        );

        self::$lenderBelongsToCompanyNotActive = $this->createLenderUser(
            self::$companyNotActive->id,
            Role::LenderAdmin
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
                'code' => 1015,
            ]);
    }

    public function test_resend_invitation_can_not_access_without_verify_email()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenerAdminNotVerified)
            ->postJson(
                'api/v1/lender/users/'.self::$lenderAdminNotJoined->id.'/resend-invitation',
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

    public function test_resend_invitation_lender_admin_can_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenerAdmin)
            ->postJson(
                'api/v1/lender/users/'.self::$lenderAdminNotJoined->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);
    }

    public function test_resend_invitation_only_lender_admin_can_access()
    {
        $roles = [Role::LenderBilling, Role::LenderApiUser, Role::LenderOrderCreator, Role::LenderSupervisor];

        $this->assertStatusCodeToSpecificRoles(403, $roles, function (User $user, string $role) {
            return  $this->actingAs($user)
                ->withHeader('X-Company', self::$company->getOriginal('id'))
                ->postJson(
                    'api/v1/lender/users/'.self::$lenderAdminNotJoined->id.'/resend-invitation',
                    [
                        'redirect_url' => self::$redirectUrl,
                    ]
                );
        });
    }

    public function test_resend_invitation_email_is_sent()
    {
        Mail::fake();
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenerAdmin)
            ->postJson(
                'api/v1/lender/users/'.self::$lenderAdminNotJoined->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);

        Mail::assertQueued(CompleteRegisterInvitation::class, function ($mail) {
            return $mail->user instanceof User;
        });

        Mail::assertQueued(CompleteRegisterInvitation::class, function ($mail) {
            return $mail->user->id == self::$lenderAdminNotJoined->id;
        });
    }
}
