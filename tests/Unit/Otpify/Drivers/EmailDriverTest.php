<?php

namespace Tests\Unit\Otpify\Drivers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeExpiredException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Exceptions\OtpCodeNotFoundException;
use Modules\Otpify\Facades\Otpify;
use Modules\Otpify\Models\OtpifyCode;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class EmailDriverTest extends TestCase
{
    use InteractsWithCompany;
    use InteractsWithUser;
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('otpify.default', 'email');
        $this->user->update(['locale' => config('app.locale')]);
    }

    public function test_email_driver_send_method_return_instanceof_otpify_code()
    {
        $otp = Otpify::send(new Request(), $this->user);

        $this->assertInstanceOf(OtpifyCode::class, $otp);
    }

    public function test_email_driver_verify_method_fails_if_otp_code_does_not_exist()
    {
        $this->expectException(OtpCodeNotFoundException::class);

        $otp = Otpify::send(new Request(), $this->user);
        $otp->delete();
        Otpify::verify(new Request(), $otp->id, '123');
    }

    public function test_email_driver_verify_method_fails_if_code_is_already_used()
    {
        $this->expectException(OtpCodeAlreadyUsedException::class);

        /** @var OtpifyCode $otp */
        $otp = Otpify::send(new Request(), $this->user);
        $otp->update(['expired_at' => now()]);
        Otpify::verify(new Request(), $otp->id, '123456');
    }

    public function test_email_driver_verify_method_fails_if_code_is_expired()
    {
        $this->expectException(OtpCodeExpiredException::class);

        $otp = Otpify::send(new Request(), $this->user);
        $otp->update(['expiration_date' => now()->subDay()]);
        Otpify::verify(new Request(), $otp->id, '123456');
    }

    public function test_email_driver_otp_code_is_fails_if_code_is_not_correct()
    {
        $this->expectException(OtpCodeIncorrectException::class);

        $otp = Otpify::send(new Request(), $this->user);
        Otpify::verify(new Request(), $otp->id, '123');
    }

    public function test_email_driver_otp_code_fails_if_additional_check_callback_returns_false()
    {
        $this->expectException(OtpCodeAdditionalCheckException::class);

        $otp = Otpify::send(new Request(), $this->user);

        Otpify::verify(new Request(), $otp->id, '123456', function ($request, $otp) {
            return false;
        });
    }

    public function test_email_driver_otp_code_fails_if_driver_different()
    {
        $this->expectException(OtpCodeNotFoundException::class);

        $otp = Otpify::send(new Request(), $this->user);
        $otp->update(['driver' => 'fake']);
        Otpify::verify(new Request(), $otp->id, '123456');
    }

    public function test_return_success_if_master_otp_correct_and_env_development()
    {
        $otp = Otpify::send(new Request(), $this->user);
        Otpify::verify(new Request(), $otp->id, config('master-otp.master_otp_key'));
        $this->assertInstanceOf(OtpifyCode::class, $otp);
    }

    public function test_return_success_if_user_check_by_the_correct_otp_and_env_development()
    {
        $otp = Otpify::send(new Request(), $this->user);
        Otpify::verify(new Request(), $otp->id, '123456');
        $this->assertInstanceOf(OtpifyCode::class, $otp);
    }

    public function test_return_false_if_development_server_but_master_code_not_correct()
    {
        $this->expectException(OtpCodeIncorrectException::class);
        $otp = Otpify::send(new Request(), $this->user);
        Otpify::verify(new Request(), $otp->id, config('master-otp.master_otp_key').'2');
    }

    public function test_return_false_if_it_not_development_server()
    {
        $this->expectException(OtpCodeIncorrectException::class);
        Config::set('master-otp.allow_verify_with_master_key', false);
        $otp = Otpify::send(new Request(), $this->user);
        Otpify::verify(new Request(), $otp->id, config('master-otp.master_otp_key'));
    }
}
