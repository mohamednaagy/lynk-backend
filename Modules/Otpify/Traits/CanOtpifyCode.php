<?php

namespace Modules\Otpify\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeExpiredException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Exceptions\OtpCodeNotFoundException;
use Modules\Otpify\Exceptions\OtpifiableNotEqualAuthUserException;
use Modules\Otpify\Facades\Otpify;
use Modules\Otpify\Models\OtpifyCode;

trait CanOtpifyCode
{
    /**
     *  createOtpifyCode
     *
     * @param  mixed  $code
     * @param  Otpifiable  $otpifiable
     * @param  Model|null  $initiator
     * @param  array  $data
     * @return OtpifyCode
     */
    public function createOtpifyCode($code, Otpifiable $otpifiable, Model $initiator = null, array $data = []): OtpifyCode
    {
        return OtpifyCode::create([
            'id' => (string) Str::uuid(),
            'initiator_id' => optional($initiator)->getKey(),
            'initiator_type' => optional($initiator)->getMorphClass(),
            'otpifiable_id' => $otpifiable->getKey(),
            'otpifiable_type' => $otpifiable->getMorphClass(),
            'otp_code' => $code,
            'expiration_date' => now()->addMinutes(config('otpify.code_expiration_time')),
            'data' => $data,
        ]);
    }

    /**
     * @param  OtpifyCode  $otpifyCode
     * @param  Request  $request
     * @param $code
     * @param  Closure|null  $additionalCheckCallback
     * @return void
     *
     * @throws OtpCodeAdditionalCheckException
     * @throws OtpCodeAlreadyUsedException
     * @throws OtpCodeExpiredException
     * @throws OtpCodeIncorrectException
     * @throws OtpifiableNotEqualAuthUserException
     */
    public function verifyOtpifyCode(OtpifyCode $otpifyCode, Request $request, $code, Closure $additionalCheckCallback = null): void
    {
        if (auth()->user()->getAuthIdentifier() !== $otpifyCode->otpifiable_id) {
            throw new OtpifiableNotEqualAuthUserException();
        }

        if (! Hash::check($code, $otpifyCode->otp_code)) {
            throw new OtpCodeIncorrectException();
        }

        if ($otpifyCode->expired_at != null) {
            throw new OtpCodeAlreadyUsedException();
        }

        if ($this->isCodeExpired($otpifyCode->expiration_date)) {
            throw new OtpCodeExpiredException();
        }

        if ($additionalCheckCallback) {
            if (! $additionalCheckCallback($request, $code)) {
                throw new OtpCodeAdditionalCheckException();
            }
        }
    }

    /**
     * @param $vid
     * @return OtpifyCode
     *
     * @throws OtpCodeNotFoundException
     */
    public function getOtpifyCode($vid): OtpifyCode
    {
        $otpifyCode = OtpifyCode::where('id', $vid)->first();
        if (! $otpifyCode) {
            throw new OtpCodeNotFoundException();
        }

        return $otpifyCode;
    }

    /**
     * @param $expirationDate
     * @return bool
     */
    public function isCodeExpired($expirationDate): bool
    {
        return $expirationDate->lt(now());
    }

    /**
     * @param  OtpifyCode  $otpifyCode
     * @return void
     */
    public function setOtpExpiredAt(OtpifyCode $otpifyCode): void
    {
        $otpifyCode->update(['expired_at' => now()]);
    }

    public function createAuthorizationToken(array $data): void
    {
        Otpify::generateAuthorizationToken($data);
    }
}
