<?php

namespace Modules\Otpify\Contracts;

use Illuminate\Http\Request;

interface Otpifiable
{
    /**
     * Execute the otpifiable logic.
     *
     * @param Request $request
     * @return bool
     */
    public function shouldAsk(Request $request): bool;
}
