<?php

namespace Modules\Otpify\Contracts;

use Illuminate\Http\Request;
use Modules\Otpify\Models\OtpifyCode;

interface OtpifyDriverInterface
{
    /**
     * Execute the driver logic.
     *
     * @param Request $request
     * @param Otpifiable $model
     * @param array $data
     * @return mixed
     */
    public function execute(Request $request, Otpifiable $model, array $data): OtpifyCode;

    public function shouldAsk(Request $request, Otpifiable $model): bool;

    public function verify(Request $request, $vid, $code, $continue): bool;

}
