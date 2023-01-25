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

    private static User $lender;

    private static User $secondLender;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        [$company] = $this->createLenderCompany();
        self::$lender = $this->createLenderUser($company->id);
        self::$secondLender = $this->createLenderUser($company->id);
    }

    public function test_send_email_verification_redirect_url_input_is_required()
    {
        $this->actingAs(self::$lender)
            ->postJson('api/v1/auth/send-email-verification')
            ->assertJsonValidationErrorFor('redirect_url');
    }

    public function test_send_email_verification_redirect_url_input_should_be_valid_url()
    {
        $this->actingAs(self::$lender)
            ->postJson('api/v1/auth/send-email-verification', ['redirect_url' => 'wrong://localhost'])
            ->assertJsonValidationErrorFor('redirect_url');
    }

    public function test_send_email_verification_redirect_url_should_be_in_app_white_list()
    {
        $this->actingAs(self::$lender)
            ->postJson('api/v1/auth/send-email-verification', ['redirect_url' => 'http://wrong-website'])
            ->assertJsonValidationErrorFor('redirect_url');
    }

    public function test_send_email_verification_email_input_should_be_unique()
    {
        $this->actingAs(self::$lender)
            ->postJson('api/v1/auth/send-email-verification', ['email' => self::$secondLender->email])
            ->assertJsonValidationErrorFor('email');
    }

    public function test_send_email_verification_update_email_if_the_user_provide_new_email()
    {
        $newEmail = 'newEmail@email.com';
        $this->actingAs(self::$lender)
            ->postJson(
                'api/v1/auth/send-email-verification',
                [
                    'redirect_url' => 'http://localhost',
                    'email' => $newEmail,
                ]
            )
            ->assertStatus(Response::HTTP_OK);

        self::$lender->refresh();

        $this->assertTrue(self::$lender->email == $newEmail);
    }

    public function test_send_email_verification_mail_sent_successfully()
    {
        Mail::fake();

        $this->actingAs(self::$lender)
            ->postJson(
                'api/v1/auth/send-email-verification',
                [
                    'redirect_url' => 'http://localhost',
                ]
            )
            ->assertStatus(200);

        Mail::assertQueued(VerifyEmail::class, function ($mail) {
            return $mail->to(self::$lender->email);
        });
    }
}
