<?php

namespace Endpoints\Api\V1\Admin\Auth;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Mail\Admin\CompleteAdminRegisterInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class ResendAdminInvitationTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static User $superAdminNotJoined;

    private static User $lenderBelongsToCompanyNotActive;

    private static User $superAdmin;

    private static User $superAdminNotVerified;

    private static string $redirectUrl;

    public function setUp(): void
    {
        parent::setUp();

        self::$redirectUrl = 'http://localhost';
        self::$superAdmin = $this->createSuperAdminUser();
        self::$superAdminNotJoined = $this->createSuperAdminUser(data: ['password' => null]);
        self::$superAdminNotVerified = $this->createSuperAdminUser(data: ['email_verified_at' => null]);
    }

    public function test_resend_invitation_super_admin_can_access()
    {
        $this->actingAs(self::$superAdmin)
            ->postJson(
                'api/v1/admin/admins/'.self::$superAdminNotJoined->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);
    }

    public function test_resend_invitation_manager_admin_can_access()
    {
        Grantify::assignRoleToModel(self::$superAdmin, Role::Manager);
        Grantify::assignPermissionToModel(self::$superAdmin, perm(Area::SuperAdmin, [Subject::Admins, Action::Create]));

        $this->actingAs(self::$superAdmin)
            ->postJson(
                'api/v1/admin/admins/'.self::$superAdminNotJoined->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);
    }

    public function test_resend_invitation_email_is_sent()
    {
        Mail::fake();
        $this->actingAs(self::$superAdmin)
            ->postJson(
                'api/v1/admin/admins/'.self::$superAdminNotJoined->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);

        Mail::assertQueued(CompleteAdminRegisterInvitation::class, function ($mail) {
            return $mail->invitee instanceof User;
        });

        Mail::assertQueued(CompleteAdminRegisterInvitation::class, function ($mail) {
            return $mail->invitee->id == self::$superAdminNotJoined->id;
        });
    }
}
