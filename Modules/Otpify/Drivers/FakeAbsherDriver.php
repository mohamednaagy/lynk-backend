<?php

namespace Modules\Otpify\Drivers;

use Closure;
use Illuminate\Http\Request;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeExpiredException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Exceptions\OtpCodeNotFoundException;
use Modules\Otpify\Models\OtpifyCode;
use Modules\Otpify\Traits\CanOtpifyCode;

class FakeAbsherDriver implements OtpifyDriverInterface
{
    use CanOtpifyCode;

    /**
     * Execute the driver logic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Modules\Otpify\Contracts\Otpifiable  $otpifiable
     * @param  array  $data
     * @return \Modules\Otpify\Models\OtpifyCode
     */
    public function send(Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode
    {
        return $this->createOtpifyCode(null, $otpifiable, $otpifiable);
    }

    /**
     * Execute the driver logic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Modules\Otpify\Contracts\Otpifiable  $otpifiable
     * @return bool
     */
    public function doesRequireVerifyingByOtp(Request $request, Otpifiable $otpifiable): bool
    {
        return $otpifiable->doesRequireVerifyingByOtp($request);
    }

    /**
     * Execute the driver logic.
     *
     * @param  Request  $request
     * @param  mixed  $vid
     * @param  mixed  $code
     * @param  \Closure|null  $additionalCheckCallback
     * @return string|bool
     *
     * @throws OtpCodeAdditionalCheckException
     * @throws OtpCodeAlreadyUsedException
     * @throws OtpCodeExpiredException
     * @throws OtpCodeIncorrectException
     * @throws OtpCodeNotFoundException
     */
    public function verify(Request $request, $vid, $code, Closure $additionalCheckCallback = null): string|bool
    {
        $otpifyCode = $this->getOtpifyCode($vid);
        if ($otpifyCode->expired_at != null) {
            throw new OtpCodeAlreadyUsedException();
        }

        if ($this->isCodeExpired($otpifyCode->expiration_date)) {
            throw new OtpCodeExpiredException();
        }

        if ($additionalCheckCallback instanceof Closure && ! $additionalCheckCallback($request, $code)) {
            throw new OtpCodeAdditionalCheckException();
        }

        if ($code === '2023') {
            $otpifyCode->otpifiable->update([
                'customer_details' => [
                    'issueLocationAr' => '',
                    'englishName' => 'Mahmod Mohammed Fahed Ali',
                    'arabicFatherName' => 'محمد',
                    'englishFatherName' => 'Mohammed',
                    'gender' => 'Male',
                    'dobHijri' => '1430/11/09',
                    'cardIssueDateHijri' => '1439/02/27',
                    'englishFirstName' => 'Mahmod',
                    'issueLocationEn' => '',
                    'cardIssueDateGregorian' => '2017/11/16',
                    'englishGrandFatherName' => 'Fahed',
                    'userid' => '2309470215',
                    'arabicGrandFatherName' => 'فهد',
                    'idVersionNo' => '4',
                    'arabicNationality' => 'الفلبين',
                    'arabicName' => 'محمود محمد فهد علي',
                    'arabicFirstName' => 'محمود',
                    'nationalityCode' => '315',
                    'nationality' => 'Philippines',
                    'dob' => '2009/10/28',
                    'englishFamilyName' => 'Ali',
                    'idExpiryDateHijri' => '1448/09/11',
                    'arabicFamilyName' => 'علي',
                    'idExpiryDateGregorian' => '2027/02/18',
                ],
            ]);

            $this->setOtpExpiredAt($otpifyCode);

            return true;
        // return $this->createAuthorizationToken($request->all());
        } else {
            throw new OtpCodeIncorrectException();
        }
    }
}
