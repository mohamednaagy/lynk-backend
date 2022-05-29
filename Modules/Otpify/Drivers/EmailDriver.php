<?php

namespace Modules\Otpify\Drivers;

use Illuminate\Http\Request;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Exceptions\OtpifyVerificationException;
use Modules\Otpify\Models\OtpifyCode;
use Modules\Otpify\Notifications\OtpifyCodeMessage;
use Modules\Otpify\Traits\OtpifiableCode;

class EmailDriver implements OtpifyDriverInterface
{
    use OtpifiableCode;

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

    public function shouldAsk(Request $request, Otpifiable $model): bool
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
     * @throws \Exception
     */
    public function verify(Request $request, $vid, $code, \Closure $additionalCheckCallback = null): bool
    {
        $otpifyCode = $otpifyCode = $this->getOtpifyCode($vid);

        $verificationMessage = $this->verifyOtpifyCode($otpifyCode, $code, $additionalCheckCallback);
        if ($verificationMessage !== 'valid')
            throw new OtpifyVerificationException($verificationMessage, 400, ['driver' => 'Email']);

        $this->setOtpExpiredAt($otpifyCode);
        return true;
    }
}
