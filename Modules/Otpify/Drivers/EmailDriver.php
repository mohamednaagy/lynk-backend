<?php

namespace Modules\Otpify\Drivers;

use Illuminate\Http\Request;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
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
    public function execute(Request $request, Otpifiable $otpifiable, array $data): OtpifyCode
    {
        $code = generateRandomCode(config('otpify.code_length'));
        $otpifyCode = $this->createOtpifyCode($data, $code);
        $otpifiable->notify(new OtpifyCodeMessage($code, $otpifyCode->expired_at));
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
     * @param $continue
     * @return bool
     */
    public function verify(Request $request, $vid, $code, $continue = null): bool
    {
        $otpifyCode = $this->getOtpifyCode($vid, $code);
        return $this->codeIsValid($otpifyCode->expired_at);
    }
}
