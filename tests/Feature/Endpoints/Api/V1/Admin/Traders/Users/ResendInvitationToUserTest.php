<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Traders\Users;

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

    private static Company $trader;

    private static User $userAdmin;

    private static User $traderUser;

    private static User $traderUserNotJoined;

    private static string $redirectUrl;

    public function setUp(): void
    {
        parent::setUp();

        self::$redirectUrl = 'http://localhost';
        self::$userAdmin = $this->createSuperAdminUser();
        [self::$trader] = $this->createTraderCompany('2000');
        self::$traderUser = $this->createTraderUser(
            self::$trader->id,
            data: ['email' => 'TraderAdmin@bim.com']
        );
        self::$traderUserNotJoined = $this->createTraderUser(
            self::$trader->id,
            data: ['password' => null]
        );
    }

    /**
     * @return void
     */
    public function test_un_auth_user_cant_index_trader_users(): void
    {
        $this->postJson(
            'api/v1/admin/traders/'.self::$trader->id.'/users/'.self::$traderUser.'/resend-invitation',
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
                'api/v1/admin/traders/'.self::$trader->id.'/users/'.self::$traderUserNotJoined->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);

        Mail::assertQueued(CompleteRegisterInvitation::class, function ($mail) {
            return $mail->user instanceof User;
        });

        Mail::assertQueued(CompleteRegisterInvitation::class, function ($mail) {
            return $mail->user->id == self::$traderUserNotJoined->id;
        });
    }

    public function test_resend_invitation_cant_be_sent_for_joined_trader_user()
    {
        Mail::fake();

        $this->actingAs(self::$userAdmin)
            ->postJson(
                'api/v1/admin/traders/'.self::$trader->id.'/users/'.self::$traderUser->id.'/resend-invitation',
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
                        'api/v1/admin/traders/'.self::$trader->id.'/users/'.self::$traderUserNotJoined->id.'/resend-invitation',
                        [
                            'redirect_url' => self::$redirectUrl,
                        ]
                    );
            }
        );
    }
}
