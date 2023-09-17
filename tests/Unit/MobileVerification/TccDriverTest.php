<?php

namespace Tests\Unit\MobileVerification;

use App\Enums\TccResponseCode;
use App\Exceptions\MobileVerification\InvalidApiKeyException;
use App\Exceptions\MobileVerification\InvalidPersonIdException;
use App\Exceptions\MobileVerification\InvalidPersonIdTypeException;
use App\Exceptions\MobileVerification\MobileNumberNotMatchedException;
use App\Support\MobileVerification\Facades\MobileVerify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Propaganistas\LaravelPhone\Exceptions\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;
use Tests\TestCase;

class TccDriverTest extends TestCase
{
    use RefreshDatabase;

    protected static string $phoneCountryCode;

    public function setUp(): void
    {
        parent::setUp();
        self::$phoneCountryCode = 'SA';
    }

    public function test_mobile_verification_tcc_that_mobile_number_matched(): void
    {
        Http::fake(function () {
            return Http::response([
                'code' => TccResponseCode::MobileNumberMatched,
            ]);
        });
        $phoneNumber = PhoneNumber::make('966547125919', self::$phoneCountryCode);
        $nationalId = '1001280070';
        $response = MobileVerify::driver('tcc')->verify($phoneNumber, $nationalId);

        $this->assertTrue($response);
    }

    public function test_mobile_verification_tcc_that_mobile_number_does_not_match_the_provided_country(): void
    {
        $this->expectException(NumberParseException::class);
        $phoneNumber = PhoneNumber::make('96654712591999', self::$phoneCountryCode);
        $nationalId = '1001280070';
        MobileVerify::driver('tcc')->verify($phoneNumber, $nationalId);
    }

    public function test_mobile_verification_tcc_that_mobile_number_unmatched(): void
    {
        Http::fake(function () {
            return Http::response([
                'code' => TccResponseCode::MobileNumberUnmatched,
            ]);
        });
        $this->expectException(MobileNumberNotMatchedException::class);
        $phoneNumber = PhoneNumber::make('500112233', self::$phoneCountryCode);
        $nationalId = '2553451234';
        MobileVerify::driver('tcc')->verify($phoneNumber, $nationalId);
    }

    public function test_mobile_verification_tcc_that_person_id_invalid(): void
    {
        Http::fake(function () {
            return Http::response([
                'code' => TccResponseCode::InvalidPersonId,
            ]);
        });
        $this->expectException(InvalidPersonIdException::class);
        $phoneNumber = PhoneNumber::make('500112233', self::$phoneCountryCode);
        $nationalId = '2553451236';
        MobileVerify::driver('tcc')->verify($phoneNumber, $nationalId);
    }

    public function test_mobile_verification_tcc_that_person_id_type_invalid(): void
    {
        Http::fake(function () {
            return Http::response([
                'code' => TccResponseCode::InvalidPersonIdType,
            ]);
        });
        $this->expectException(InvalidPersonIdTypeException::class);
        $phoneNumber = PhoneNumber::make('500112233', self::$phoneCountryCode);
        $nationalId = '8553451236';
        MobileVerify::driver('tcc')->verify($phoneNumber, $nationalId);
    }

    public function test_mobile_verification_tcc_that_api_key_invalid(): void
    {
        Http::fake(function () {
            return Http::response([
                'code' => TccResponseCode::InvalidApiKey,
            ]);
        });
        Config::set('mobile-verify.drivers.tcc.api_key', '122385904480654204103/UjUEigInUUt8dzlpTP2PllXBsPQXHuPIqjYVDmg=');
        $this->expectException(InvalidApiKeyException::class);
        $phoneNumber = PhoneNumber::make('500112233', self::$phoneCountryCode);
        $nationalId = '2553451234';
        MobileVerify::driver('tcc')->verify($phoneNumber, $nationalId);
    }
}
