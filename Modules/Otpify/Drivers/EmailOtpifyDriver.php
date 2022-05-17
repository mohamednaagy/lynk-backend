<?php

namespace Modules\Otpify\Drivers;

use Illuminate\Http\Request;
use Modules\Otpify\Contracts\Otpifiable;
use Modules\Otpify\Contracts\OtpifyDriverInterface;
use Modules\Otpify\Entities\OtpifyCode;
use Modules\Otpify\Notifications\OtpifyCodeMessage;
use Modules\Otpify\Traits\GenerateOtpifyCode;

class EmailOtpifyDriver implements OtpifyDriverInterface
{
    use GenerateOtpifyCode;

    /**
     * Execute the driver logic.
     *
     * @param Request $request
     * @param Otpifiable $model
     * @param array $data
     * @return OtpifyCode
     */
    public function execute(Request $request, Otpifiable $model, array $data): OtpifyCode
    {
        $otpifyCode = $this->createOtpifyCode($data);
        $model->notify(new OtpifyCodeMessage($otpifyCode->otp_code, $otpifyCode->expired_at));
        return $otpifyCode;
    }

    public function shouldAsk(Request $request, Otpifiable $model): bool
    {
        // TODO: Implement shouldAsk() method.
    }

    public function verify(Request $request, $vid, $code): bool
    {
        // TODO: Implement verify() method.
    }
}
