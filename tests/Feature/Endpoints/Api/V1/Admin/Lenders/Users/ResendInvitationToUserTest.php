<?php

namespace Endpoints\Api\V1\Admin\Lenders\Users;

use App\Enums\Area;
use App\Mail\CompleteRegisterInvitation;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class ResendInvitationToUserTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $lender;

    private static User $userAdmin;

    private static User $lenderUser;

    private static User $lenderUserNotJoined;

    private static string $redirectUrl;

    public function setUp(): void
    {
        parent::setUp();

        self::$redirectUrl = 'http://localhost';
        self::$userAdmin = $this->createSuperAdminUser();
        [self::$lender] = $this->createLenderCompany('2000');
        self::$lenderUser = $this->createLenderUser(
            self::$lender->id,
            data: ['email' => 'LenderAdmin@bim.com']
        );
        self::$lenderUserNotJoined = $this->createTraderUser(
            self::$lender->id,
            data: ['password' => null]
        );
    }

    public function test_un_auth_user_cant_index_trader_users(): void
    {
        $this->postJson(
            'api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$lenderUser.'/resend-invitation',
            [
                'redirect_url' => self::$redirectUrl,
            ]
        )
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_resend_invitation_is_sent_for_not_joined_trader_user()
    {
        Mail::fake();

        $this->actingAs(self::$userAdmin)
            ->postJson(
                'api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$lenderUserNotJoined->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);

        Mail::assertQueued(CompleteRegisterInvitation::class, function ($mail) {
            return $mail->user instanceof User;
        });

        Mail::assertQueued(CompleteRegisterInvitation::class, function ($mail) {
            return $mail->user->id == self::$lenderUserNotJoined->id;
        });
    }

    public function test_resend_invitation_cant_be_sent_for_joined_trader_user()
    {
        Mail::fake();

        $this->actingAs(self::$userAdmin)
            ->postJson(
                'api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$lenderUser->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);

        Mail::assertNothingQueued();
    }

    public function test_other_area_roles_of_not_admin_area_cant_access_resend_invitation()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [
                Area::SuperAdmin,
            ],
            function ($user, $role) {
                return $this->actingAs($user)
                    ->postJson(
                        'api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$lenderUserNotJoined->id.'/resend-invitation',
                        [
                            'redirect_url' => self::$redirectUrl,
                        ]
                    );
            }
        );
    }
}
