<?php

namespace Tests\Unit\Otpify\Drivers;

use App\Enums\Role;
use App\Models\FinancingOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Otpify\Drivers\AbsherDriver;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeExpiredException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Exceptions\OtpCodeNotFoundException;
use Modules\Otpify\Models\OtpifyCode;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class AbsherDriverTest extends TestCase
{
    use InteractsWithLender;
    use RefreshDatabase;

    protected AbsherDriver $driver;

    protected FinancingOrder $financingOrder;

    public function setUp(): void
    {
        parent::setUp();

        $company = $this->createCompany('2000', ['company_cr' => '12345678910'])[0];
        $lender = $this->createLenderUser($company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');

        $this->financingOrder = $this->createOrder($company->id, $lender->id);

        $this->driver = new AbsherDriver(
            baseUrl: config('otpify.drivers.absher.base_url'),
            apiKey: config('otpify.drivers.absher.api_key')
        );
    }

    public function test_send_method_return_instanceof_otpify_code()
    {
        $otp = $this->driver->send(new Request(), $this->financingOrder);

        $this->assertInstanceOf(OtpifyCode::class, $otp);
    }

    public function test_verify_method_throw_exception_when_code_is_wrong()
    {
        $this->expectException(OtpCodeIncorrectException::class);

        $otp = $this->driver->send(new Request(), $this->financingOrder);

        $this->driver->verify(new Request(), $otp->id, 123);
    }

    public function test_verify_method_throw_exception_when_tcn_is_not_exists()
    {
        $this->expectException(OtpCodeNotFoundException::class);

        $otp = $this->driver->send(new Request(), $this->financingOrder);
        $otp->update(['data' => []]);
        $this->driver->verify(new Request(), $otp->id, 123);
    }

    public function test_verify_method_throw_exception_when_code_used()
    {
        $this->expectException(OtpCodeAlreadyUsedException::class);

        $otp = $this->driver->send(new Request(), $this->financingOrder);
        $otp->update(['expired_at' => now()]);
        $this->driver->verify(new Request(), $otp->id, 123);
    }

    public function test_verify_method_throw_exception_when_code_expired()
    {
        $this->expectException(OtpCodeExpiredException::class);

        $otp = $this->driver->send(new Request(), $this->financingOrder);
        $otp->update(['expiration_date' => now()->subDay()]);
        $this->driver->verify(new Request(), $otp->id, 123);
    }
}
