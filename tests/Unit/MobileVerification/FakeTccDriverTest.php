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

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        self::$phoneCountryCode = 'SA';
    }

    /**
     * @return void
     */
    public function test_mobile_verification_tcc_that_mobile_number_matched(): void
    {
        $phoneNumber = PhoneNumber::make('500112233', self::$phoneCountryCode);
        $nationalId = '1001280070';
        $response = MobileVerify::driver('fake_tcc')->verify($phoneNumber, $nationalId);

        $this->assertTrue($response);
    }

    /**
     * @return void
     */
    public function test_mobile_verification_tcc_that_mobile_number_unmatched(): void
    {
        $this->expectException(MobileNumberNotMatchedException::class);
        $phoneNumber = PhoneNumber::make('500114455', self::$phoneCountryCode);
        $nationalId = '2553451234';
        MobileVerify::driver('fake_tcc')->verify($phoneNumber, $nationalId);
    }
}
