<?php

namespace Modules\Otpify\Drivers;

use Closure;
use Illuminate\Http\Request;
use Modules\Otpify\Models\OtpifyCode;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Traits\CanOtpifyCode;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Notifications\OtpifyCodeMessage;
use Modules\Otpify\Exceptions\OtpCodeExpiredException;
use Modules\Otpify\Exceptions\OtpCodeNotFoundException;
use Modules\Otpify\Exceptions\OtpCodeIncorrectException;
use Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException;
use Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException;

class EmailDriver implements OtpifyDriverInterface
{
    use CanOtpifyCode;

    /**
     * Execute the driver logic.
     *
     * @param Request $request
     * @param Otpifiable $otpifiable
     * @param array $data
     * @return OtpifyCode
     */
    public function send(Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode
    {
        $code = generateRandomCode(config('otpify.code_length'));
        $otpifiableId = $request->get('otpifiable_id') ?? auth()->user()->getAuthIdentifier();
        $otpifyCode = $this->createOtpifyCode($code, $otpifiableId, $data);
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
     * @param Request $request
     * @param $vid
     * @param $code
     * @param Closure|null $additionalCheckCallback
     * @return bool
     * @throws OtpCodeAlreadyUsedException
     * @throws OtpCodeAdditionalCheckException
     * @throws OtpCodeExpiredException
     * @throws OtpCodeIncorrectException
     * @throws OtpCodeNotFoundException
     */
    public function verify(Request $request, $vid, $code, Closure $additionalCheckCallback = null): bool
    {
        $otpifyCode = $this->getOtpifyCode($vid);
        $this->verifyOtpifyCode($otpifyCode, $request, $code, $additionalCheckCallback);
        $this->setOtpExpiredAt($otpifyCode);

        return true;
    }
}
