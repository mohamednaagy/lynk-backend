<?php

namespace App\Support\MobileVerification\Drivers;

use App\Enums\TccPersonIdType;
use App\Enums\TccResponseCode;
use App\Exceptions\MobileVerification\InvalidMobileNumberException;
use App\Exceptions\MobileVerification\InvalidPersonIdException;
use App\Exceptions\MobileVerification\MobileNumberNotMatchedException;
use App\Exceptions\MobileVerification\PersonNotFoundException;
use App\Support\MobileVerification\Contracts\MobileVerifyDriverInterface;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Propaganistas\LaravelPhone\PhoneNumber;

class TccDriver implements MobileVerifyDriverInterface
{
    /**
     * @param  PhoneNumber  $mobileNumber
     * @param  string  $personId
     * @return bool
     *
     * @throws PersonNotFoundException
     * @throws InvalidPersonIdException
     * @throws InvalidMobileNumberException
     * @throws MobileNumberNotMatchedException
     */
    public function verify(PhoneNumber $mobileNumber, string $personId): bool
    {
        $url = $this->url('TCC-Web/api/mobile/verify');

        $response = Http::post(
            $url,
            $this->prepareRequestData(ltrim($mobileNumber->formatE164(), '+'), $personId)
        );

        $response = $response->json();

        activity()
            ->withProperties(['response' => $response])
            ->log('Mobile Number Verification');

        return $this->verifyResponse($response);
    }

    /**
     * @param  string  $mobileNumber
     * @param  string  $personId
     * @return array
     *
     * @throws InvalidPersonIdException
     */
    private function prepareRequestData(string $mobileNumber, string $personId): array
    {
        return [
            'apiKey' => config('mobile-verify.drivers.tcc.api_key'),
            'operatorTCN' => Str::random(25),
            'mobileNumber' => $mobileNumber,
            'personId' => $personId,
            'personIdType' => $this->getPersonIdType($personId),
        ];
    }

    /**
     * @param  string  $personId
     * @return int
     *
     * @throws InvalidPersonIdException
     */
    private function getPersonIdType(string $personId): int
    {
        $typeNumber = substr($personId, 0, 1);

        if (! in_array($typeNumber, TccPersonIdType::getValues())) {
            throw new InvalidPersonIdException();
        }

        return $typeNumber;
    }

    /**
     * @param  array  $response
     * @return bool
     *
     * @throws Exception
     * @throws PersonNotFoundException
     * @throws InvalidPersonIdException
     * @throws InvalidMobileNumberException
     * @throws MobileNumberNotMatchedException
     */
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

    public function url($path)
    {
        return rtrim(Config::get('mobile-verify.drivers.tcc.base_url'), '/').'/'.ltrim($path, '/');
    }
}
