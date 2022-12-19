<?php

namespace App\Support\MobileVerification\Drivers;

use App\Enums\TccResponseCode;
use App\Exceptions\MobileVerification\MobileNumberNotMatchedException;
use App\Support\MobileVerification\Contracts\MobileVerifyDriverInterface;
use Propaganistas\LaravelPhone\PhoneNumber;

class FakeTccDriver implements MobileVerifyDriverInterface
{
    /**
     * @param  \Propaganistas\LaravelPhone\PhoneNumber  $mobileNumber
     * @param  string  $personId
     * @return bool
     */
    public function verify(PhoneNumber $mobileNumber, string $personId): bool
    {
        $responseCode = $mobileNumber->formatE164() == '+966500112233'
            ? TccResponseCode::MobileNumberMatched
            : TccResponseCode::MobileNumberUnmatched;

        return $this->verifyResponse(['code' => $responseCode]);
    }

    private function verifyResponse(array $response): bool
    {
        switch ($response['code']) {
            case TccResponseCode::MobileNumberMatched:
                return true;
            case TccResponseCode::MobileNumberUnmatched:
                throw new MobileNumberNotMatchedException();
        }
    }
}
