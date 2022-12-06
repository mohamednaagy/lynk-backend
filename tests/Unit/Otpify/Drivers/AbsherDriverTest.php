<?php

namespace Tests\Unit\Otpify\Drivers;

use App\Enums\Role;
use App\Models\FinancingOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeExpiredException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Exceptions\OtpCodeNotFoundException;
use Modules\Otpify\Facades\Otpify;
use Modules\Otpify\Models\OtpifyCode;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class AbsherDriverTest extends TestCase
{
    use InteractsWithLender;
    use RefreshDatabase;

    protected static FinancingOrder $financingOrder;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('otpify.default', 'Absher');
        $company = $this->createCompany('2000', ['company_cr' => '12345678910'])[0];
        $lender = $this->createLenderUser($company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');

        self::$financingOrder = $this->createOrder($company->id, $lender->id);
    }

    public function test_absher_driver_send_method_return_instanceof_otpify_code()
    {
        $otp = Otpify::send(new Request(), self::$financingOrder);

        $this->assertInstanceOf(OtpifyCode::class, $otp);
    }

    public function test_absher_driver_verify_method_tcn_is_not_exists()
    {
        $this->expectException(OtpCodeNotFoundException::class);

        $otp = Otpify::send(new Request(), self::$financingOrder);
        $otp->update(['data' => []]);
        Otpify::verify(new Request(), $otp->id, 123);
    }

    public function test_absher_driver_verify_method_code_is_used()
    {
        $this->expectException(OtpCodeAlreadyUsedException::class);

        $otp = Otpify::send(new Request(), self::$financingOrder);
        $otp->update(['expired_at' => now()]);
        Otpify::verify(new Request(), $otp->id, 123);
    }

    public function test_absher_driver_verify_method_code_is_expired()
    {
        $this->expectException(OtpCodeExpiredException::class);

        $otp = Otpify::send(new Request(), self::$financingOrder);
        $otp->update(['expiration_date' => now()->subDay()]);
        Otpify::verify(new Request(), $otp->id, 123);
    }

    public function test_absher_driver_otp_code_is_not_correct()
    {
        $this->expectException(OtpCodeIncorrectException::class);

        $otp = Otpify::send(new Request(), self::$financingOrder);
        Otpify::verify(new Request(), $otp->id, 123);
    }

    public function test_absher_driver_otp_code_additional_check_callback_exception()
    {
        $this->expectException(OtpCodeAdditionalCheckException::class);

        $otp = Otpify::send(new Request(), self::$financingOrder);

        Otpify::verify(new Request(), $otp->id, 123, function ($request, $otp) {
            return false;
        });
    }
}
