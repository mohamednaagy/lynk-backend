<?php

namespace Tests\Feature\Endpoints\Api\V1\Auth;

use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class SendEmailVerificationTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    const Endpoint = 'api/v1/auth/send-email-verification';

    private static User $lenderUser;

    private static User $secondLenderUser;

    private static User $superAdmin;

    private static User $anotherSuperAdmin;

    private static string $whitelistedUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        [$company] = $this->createLenderCompany();
        self::$lenderUser = $this->createLenderUser($company->id, data: ['email' => 'user1@gmail.com']);
        self::$secondLenderUser = $this->createLenderUser($company->id, data: ['email' => 'user2@gmail.com']);

        self::$superAdmin = $this->createSuperAdminUser(data: ['email' => 'user3@gmail.com']);
        self::$anotherSuperAdmin = $this->createSuperAdminUser(data: ['email' => 'user4@gmail.com']);

        self::$whitelistedUrl = 'http://localhost';
        config([
            'app.host_whitelist' => ['localhost'],
        ]);
    }

    public function test_send_email_verification_redirect_url_input_is_required()
    {
        $this->actingAs(self::$lenderUser)
            ->postJson(self::Endpoint)
            ->assertJsonValidationErrorFor('redirect_url');
    }

    public function test_send_email_verification_redirect_url_input_should_be_valid_url()
    {
        $this->actingAs(self::$lenderUser)
            ->postJson(self::Endpoint, ['redirect_url' => 'wrong://localhost'])
            ->assertJsonValidationErrorFor('redirect_url');
    }

    public function test_send_email_verification_redirect_url_should_be_in_app_white_list()
    {
        $this->actingAs(self::$lenderUser)
            ->postJson(self::Endpoint, ['redirect_url' => 'http://wrong-website'])
            ->assertJsonValidationErrorFor('redirect_url');
    }

    public function test_send_email_verification_for_company_user_that_email_input_should_be_scoped_to_his_company_users()
    {
        $this->actingAs(self::$lenderUser)
            ->postJson(self::Endpoint, ['email' => self::$secondLenderUser->email])
            ->assertJsonValidationErrorFor('email');

        $this->actingAs(self::$lenderUser)
            ->postJson(self::Endpoint, [
                'email' => self::$superAdmin->email,
                'redirect_url' => self::$whitelistedUrl,
            ])
            ->assertStatus(Response::HTTP_OK);

        $this->assertTrue(self::$lenderUser->fresh()->email == self::$superAdmin->email);
    }

    public function test_send_email_verification_for_user_who_doesnt_belong_to_comapny_will_ignore_companys_users()
    {
        $this->actingAs(self::$superAdmin)
            ->postJson(self::Endpoint, ['email' => self::$anotherSuperAdmin->email])
            ->assertJsonValidationErrorFor('email');

        $this->actingAs(self::$superAdmin)
            ->postJson(self::Endpoint, [
                'email' => self::$lenderUser->email,
                'redirect_url' => self::$whitelistedUrl,
            ])
            ->assertStatus(Response::HTTP_OK);

        $this->assertTrue(self::$superAdmin->fresh()->email == self::$lenderUser->email);
    }

    public function test_send_email_verification_update_email_if_the_user_provide_new_email()
    {
        $newEmail = 'newEmail@email.com';
        $this->actingAs(self::$lenderUser)
            ->postJson(
                self::Endpoint,
                [
                    'redirect_url' => self::$whitelistedUrl,
                    'email' => $newEmail,
                ]
            )
            ->assertStatus(Response::HTTP_OK);

        self::$lenderUser->refresh();

        $this->assertTrue(self::$lenderUser->email == $newEmail);
    }

    public function test_send_email_verification_mail_sent_successfully()
    {
        Mail::fake();

        $this->actingAs(self::$lenderUser)
            ->postJson(
                self::Endpoint,
                [
                    'redirect_url' => self::$whitelistedUrl,
                ]
            )
            ->assertStatus(200);

        Mail::assertQueued(VerifyEmail::class, function ($mail) {
            return $mail->to(self::$lenderUser->email);
        });
    }
}
