<?php

namespace Tests\Feature\Endpoints\Api\V1\Trader\Users;

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
use Tests\Traits\InteractsWithTrader;

class ResendInvitationTest extends TestCase
{
    use RefreshDatabase, InteractsWithTrader, InteractsWithLender;

    private static Company $trader;

    private static Company $traderNotActive;

    private static User $traderAdminNotJoined;

    private static User $traderBelongsToTraderNotActive;

    private static User $traderAdmin;

    private static User $traderAdminBelonsToTraderNotActive;

    private static User $traderAdminNotVerified;

    private static string $redirectUrl;

    public function setUp(): void
    {
        parent::setUp();

        self::$redirectUrl = 'http://localhost';
        [self::$trader] = $this->createTrader('2000');
        [self::$traderNotActive] = $this->createTrader(
            '2000',
            [
                'status' => CompanyStatus::Pending,
                'company_cr' => '12345678999',
            ]
        );

        self::$traderAdmin = $this->createTraderUser(self::$trader->id, Role::TraderAdmin, 'TraderAdmin@bim.com');
        self::$traderAdminNotJoined = $this->createTraderUser(self::$trader->id, Role::TraderAdmin, 'TraderAdmin1@bim.com', ['password' => null]);
        self::$traderAdminBelonsToTraderNotActive = $this->createTraderUser(self::$traderNotActive->id, Role::TraderAdmin, 'TraderAdmin2@bim.com');

        self::$traderAdminNotVerified = $this->createTraderUser(
            self::$trader->id,
            Role::TraderAdmin,
            'TraderAdmin@bim.com',
            ['email_verified_at' => null]
        );

        self::$traderBelongsToTraderNotActive = $this->createTraderUser(
            self::$traderNotActive->id,
            Role::TraderAdmin,
            'user@bim.com'
        );
    }

    public function test_resend_invitation_can_not_access_when_trader_not_active()
    {
        $this->withHeader('X-Company', self::$traderNotActive->id)
            ->actingAs(self::$traderAdminBelonsToTraderNotActive)
            ->postJson(
                'api/v1/trader/users/'.self::$traderBelongsToTraderNotActive->id.'/resend-invitation',
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
        $this->withHeader('X-Company', self::$trader->id)
            ->actingAs(self::$traderAdminNotVerified)
            ->postJson(
                'api/v1/trader/users/'.self::$traderAdminNotJoined->id.'/resend-invitation',
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

    public function test_resend_invitation_trader_admin_can_access()
    {
        $this->withHeader('X-Company', self::$trader->id)
            ->actingAs(self::$traderAdmin)
            ->postJson(
                'api/v1/trader/users/'.self::$traderAdminNotJoined->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);
    }

    public function test_resend_invitation_only_trader_admin_can_access()
    {
        $roles = [Role::LenderBilling, Role::LenderApiUser, Role::LenderOrderCreator, Role::LenderSupervisor];

        $this->assertStatusToSpecificRoles(403, $roles, self::$trader, function (User $user, string $role) {
            return  $this->actingAs($user)
                ->withHeader('X-Company', self::$trader->getOriginal('id'))
                ->postJson(
                    'api/v1/trader/users/'.self::$traderAdminNotJoined->id.'/resend-invitation',
                    [
                        'redirect_url' => self::$redirectUrl,
                    ]
                );
        });
    }

    public function test_resend_invitation_email_is_sent()
    {
        Mail::fake();
        $this->withHeader('X-Company', self::$trader->id)
            ->actingAs(self::$traderAdmin)
            ->postJson(
                'api/v1/trader/users/'.self::$traderAdminNotJoined->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);

        Mail::assertQueued(CompleteRegisterInvitation::class, function ($mail) {
            return $mail->user instanceof User;
        });

        Mail::assertQueued(CompleteRegisterInvitation::class, function ($mail) {
            return $mail->user->id == self::$traderAdminNotJoined->id;
        });
    }
}
