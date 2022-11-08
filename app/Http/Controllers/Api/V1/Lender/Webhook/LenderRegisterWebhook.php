<?php

namespace App\Http\Controllers\Api\V1\Lender\Webhook;

use App\Actions\Contracts\CreateWebhook;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Webhook\LenderRegisterWebhookRequest;
use App\Transformers\WebhookTransformer;

class LenderRegisterWebhook extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(LenderRegisterWebhookRequest $request, CreateWebhook $createWebhook)
    {
        return fractal($createWebhook->handle($request->validated()), new WebhookTransformer())->respond();
    }
}
