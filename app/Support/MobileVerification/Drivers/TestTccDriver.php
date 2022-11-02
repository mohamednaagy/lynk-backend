<?php

namespace App\Support\MobileVerification\Drivers;

use App\Enums\TccResponseCode;
use App\Exceptions\MobileVerification\InvalidMobileNumberException;
use App\Exceptions\MobileVerification\InvalidPersonIdException;
use App\Exceptions\MobileVerification\MobileNumberNotMatchedException;
use App\Exceptions\MobileVerification\PersonNotFoundException;
use App\Support\MobileVerification\Contracts\MobileVerifyDriverInterface;
use Exception;

class TestTccDriver implements MobileVerifyDriverInterface
{
    /**
     * @param  string  $mobileNumber
     * @param  string  $personId
     * @return bool
     */
    public function verify(string $mobileNumber, string $personId): bool
    {
        $responseCode = $mobileNumber == '0500112233'
            ? TccResponseCode::MobileNumberMatched
            : TccResponseCode::MobileNumberUnMatched;

        return $this->verifyResponse(['code' => $responseCode]);
    }

    private function verifyResponse(array $response): bool
    {
        switch ($response['code']) {
            case TccResponseCode::MobileNumberMatched:
                return true;
            case TccResponseCode::MobileNumberUnMatched:
                throw new MobileNumberNotMatchedException();
            case TccResponseCode::InvalidMobileNumber:
                throw new InvalidMobileNumberException();
            case TccResponseCode::PersonNotFound:
                throw new PersonNotFoundException();
            case TccResponseCode::InvalidPersonId:
                throw new InvalidPersonIdException();
            default:
                throw new Exception();
        }
    }
}
