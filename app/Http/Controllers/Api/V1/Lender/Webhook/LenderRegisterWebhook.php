<?php

namespace App\Http\Controllers\Api\V1\Lender\Webhook;

use App\Actions\Contracts\Companies\UpdateCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Webhook\LenderRegisterWebhookRequest;

class LenderRegisterWebhook extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(LenderRegisterWebhookRequest $request, UpdateCompany $updateCompany)
    {
        $updateCompany->handle(tenant(), $request->validated());

        return $this->successResponse();
    }
}
