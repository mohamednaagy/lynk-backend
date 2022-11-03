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
use Modules\Otpify\Exceptions\OtpifiableNotEqualAuthUserException;
use Modules\Otpify\Models\OtpifyCode;
use Modules\Otpify\Notifications\OtpifyCodeMessage;
use Modules\Otpify\Traits\CanOtpifyCode;

class EmailDriver implements OtpifyDriverInterface
{
    use CanOtpifyCode;

    /**
     * Execute the driver logic.
     *
     * @param  Request  $request
     * @param  Otpifiable  $otpifiable
     * @param  array  $data
     * @return OtpifyCode
     */
    public function send(Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode
    {
        $code = generateRandomCode(config('otpify.code_length'));
        $otpifiable = $request->get('otpifiable_id') ?? auth()->user();
        $otpifyCode = $this->createOtpifyCode($code, $otpifiable, auth()->user(), $data);
        $otpifiable->notify(new OtpifyCodeMessage($code, $otpifyCode->expiration_date));

        return $otpifyCode;
    }

    public function doesRequireVerifyingByOtp(Request $request, Otpifiable $otpifiable): bool
    {
        return $otpifiable->doesRequireVerifyingByOtp($request);
    }

    /**
     * Execute the driver logic.
     *
     * @param  Request  $request
     * @param $vid
     * @param $code
     * @param  Closure|null  $additionalCheckCallback
     * @return bool
     *
     * @throws OtpCodeAlreadyUsedException
     * @throws OtpCodeAdditionalCheckException
     * @throws OtpCodeExpiredException
     * @throws OtpCodeIncorrectException
     * @throws OtpCodeNotFoundException
     * @throws OtpifiableNotEqualAuthUserException
     */
    public function verify(Request $request, $vid, $code, Closure $additionalCheckCallback = null): bool|string
    {
        $otpifyCode = $this->getOtpifyCode($vid);
        $this->verifyOtpifyCode($otpifyCode, $request, $code, $additionalCheckCallback);
        $this->setOtpExpiredAt($otpifyCode);
        $this->createAuthorizationToken($request->all());

        return true;
    }
}
