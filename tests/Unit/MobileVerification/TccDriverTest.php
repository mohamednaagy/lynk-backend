<?php

namespace Tests\Unit\MobileVerification;

use App\Exceptions\MobileVerification\InvalidApiKeyException;
use App\Exceptions\MobileVerification\InvalidPersonIdException;
use App\Exceptions\MobileVerification\InvalidPersonIdTypeException;
use App\Exceptions\MobileVerification\MobileNumberNotMatchedException;
use App\Support\MobileVerification\Facades\MobileVerify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Propaganistas\LaravelPhone\Exceptions\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;
use Tests\TestCase;

class TccDriverTest extends TestCase
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
        $phoneNumber = PhoneNumber::make('966547125919', self::$phoneCountryCode);
        $nationalId = '1001280070';
        $response = MobileVerify::driver('tcc')->verify($phoneNumber, $nationalId);

        $this->assertTrue($response);
    }

    /**
     * @return void
     */
    public function test_mobile_verification_tcc_that_mobile_number_does_not_match_the_provided_country(): void
    {
        $this->expectException(NumberParseException::class);
        $this->expectErrorMessage('Number does not match the provided country.');
        $phoneNumber = PhoneNumber::make('96654712591999', self::$phoneCountryCode);
        $nationalId = '1001280070';
        MobileVerify::driver('tcc')->verify($phoneNumber, $nationalId);
    }

    /**
     * @return void
     */
    public function test_mobile_verification_tcc_that_mobile_number_unmatched(): void
    {
        $this->expectException(MobileNumberNotMatchedException::class);
        $phoneNumber = PhoneNumber::make('500112233', self::$phoneCountryCode);
        $nationalId = '2553451234';
        MobileVerify::driver('tcc')->verify($phoneNumber, $nationalId);
    }

    /**
     * @return void
     */
    public function test_mobile_verification_tcc_that_person_id_invalid(): void
    {
        $this->expectException(InvalidPersonIdException::class);
        $phoneNumber = PhoneNumber::make('500112233', self::$phoneCountryCode);
        $nationalId = '2553451236';
        MobileVerify::driver('tcc')->verify($phoneNumber, $nationalId);
    }

    /**
     * @return void
     */
    public function test_mobile_verification_tcc_that_person_id_type_invalid(): void
    {
        $this->expectException(InvalidPersonIdTypeException::class);
        $phoneNumber = PhoneNumber::make('500112233', self::$phoneCountryCode);
        $nationalId = '8553451236';
        MobileVerify::driver('tcc')->verify($phoneNumber, $nationalId);
    }

    /**
     * @return void
     */
    public function test_mobile_verification_tcc_that_api_key_invalid(): void
    {
        //valid api key is 9122385904480654204103/UjUEigInUUt8dzlpTP2PllXBsPQXHuPIqjYVDmg=
        Config::set('mobile-verify.drivers.tcc.api_key', '122385904480654204103/UjUEigInUUt8dzlpTP2PllXBsPQXHuPIqjYVDmg=');
        $this->expectException(InvalidApiKeyException::class);
        $phoneNumber = PhoneNumber::make('500112233', self::$phoneCountryCode);
        $nationalId = '2553451234';
        MobileVerify::driver('tcc')->verify($phoneNumber, $nationalId);
    }
}
