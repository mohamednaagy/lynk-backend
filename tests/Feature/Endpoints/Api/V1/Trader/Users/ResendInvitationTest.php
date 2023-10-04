<?php

namespace Tests\Feature\Endpoints\Api\V1\Trader\Users;

use App\Enums\Area;
use App\Enums\CompanyStatus;
use App\Mail\CompleteRegisterInvitation;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class ResendInvitationTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    private static Company $trader;

    private static Company $traderNotActive;

    private static User $traderAdminNotJoined;

    private static User $traderBelongsToTraderNotActive;

    private static User $traderAdmin;

    private static User $traderAdminBelongsToTraderNotActive;

    private static User $traderAdminNotVerified;

    private static string $redirectUrl;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$redirectUrl = 'http://localhost';
        [self::$trader] = $this->createCompany('2000');
        [self::$traderNotActive] = $this->createCompany(
            '2000',
            [
                'status' => CompanyStatus::Pending,
                'company_cr' => '12345678999',
            ]
        );

        self::$traderAdmin = $this->createTraderUser(
            self::$trader->id,
            data: ['email' => 'TraderAdmin@bim.com']
        );
        self::$traderAdminNotJoined = $this->createTraderUser(
            self::$trader->id,
            data: [
                'email' => 'TraderAdmin1@bim.com',
                'password' => null,
            ]);
        self::$traderAdminBelongsToTraderNotActive = $this->createTraderUser(
            self::$traderNotActive->id,
            data: ['email' => 'TraderAdmin2@bim.com']
        );

        self::$traderAdminNotVerified = $this->createTraderUser(
            self::$trader->id,
            data: ['email_verified_at' => null]
        );

        self::$traderBelongsToTraderNotActive = $this->createTraderUser(
            self::$traderNotActive->id,
            data: ['email' => 'user@bim.com'],
        );
    }

    public function test_resend_invitation_can_not_access_when_trader_not_active()
    {
        $this->withHeader('X-Company', self::$traderNotActive->id)
            ->actingAs(self::$traderAdminBelongsToTraderNotActive)
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

    public function test_other_area_roles_of_not_trader_area_cant_access_resend_invitation()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [
                Area::Trader,
            ],
            function ($user, $role) {
                return $this->actingAs($user)
                    ->withHeader('X-Company', self::$trader->getOriginal('id'))
                    ->postJson(
                        'api/v1/trader/users/'.self::$traderAdminNotJoined->id.'/resend-invitation',
                        [
                            'redirect_url' => self::$redirectUrl,
                        ]
                    );
            }
        );
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
