<?php

namespace Modules\Otpify\Drivers;

use Illuminate\Http\Request;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Models\OtpifyCode;
use Modules\Otpify\Notifications\OtpifyCodeMessage;
use Modules\Otpify\Traits\CanOtpifyCode;

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
    public function execute(Request $request, Otpifiable $otpifiable, array $data = []): OtpifyCode
    {
        $code = generateRandomCode(config('otpify.code_length'));
        $otpifyCode = $this->createOtpifyCode($code, $data);
        $otpifiable->notify(new OtpifyCodeMessage($code, $otpifyCode->expiration_date));
        return $otpifyCode;
    }

    public function shouldAsk(Request $request, Otpifiable $otpifiable): bool
    {
        // TODO: Implement shouldAsk() method.
    }

    /**
     * Execute the driver logic.
     *
     * @param Request $request
     * @param $vid
     * @param $code
     * @param \Closure|null $additionalCheckCallback
     * @return bool
     * @throws \Modules\Otpify\Exceptions\OtpCodeAlreadyUsedException
     * @throws \Modules\Otpify\Exceptions\OtpCodeAdditionalCheckException
     * @throws \Modules\Otpify\Exceptions\OtpCodeExpiredException
     * @throws \Modules\Otpify\Exceptions\OtpCodeIncorrectException
     * @throws \Modules\Otpify\Exceptions\OtpCodeNotExistException
     */
    public function verify(Request $request, $vid, $code, \Closure $additionalCheckCallback = null): bool
    {
        $otpifyCode = $this->getOtpifyCode($vid);
        $this->verifyOtpifyCode($otpifyCode, $request, $code, $additionalCheckCallback);
        $this->setOtpExpiredAt($otpifyCode);

        return true;
    }
}
