<?php

namespace Tests\Unit\MobileVerification;

use App\Exceptions\MobileVerification\MobileNumberNotMatchedException;
use App\Support\MobileVerification\Facades\MobileVerify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Propaganistas\LaravelPhone\PhoneNumber;
use Tests\TestCase;

class FakeTccDriverTest extends TestCase
{
    use RefreshDatabase;

    protected static string $phoneCountryCode;

    protected function setUp(): void
    {
        parent::setUp();
        self::$phoneCountryCode = 'SA';
    }

    public function test_mobile_verification_tcc_that_mobile_number_matched(): void
    {
        $phoneNumber = new PhoneNumber('500112233', self::$phoneCountryCode);
        $nationalId = '1001280070';
        $response = MobileVerify::driver('fake_tcc')->verify($phoneNumber, $nationalId);

        $this->assertTrue($response);
    }

    public function test_mobile_verification_tcc_that_mobile_number_unmatched(): void
    {
        $this->expectException(MobileNumberNotMatchedException::class);
        $phoneNumber = new PhoneNumber('500114455', self::$phoneCountryCode);
        $nationalId = '2553451234';
        MobileVerify::driver('fake_tcc')->verify($phoneNumber, $nationalId);
    }
}
