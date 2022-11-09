<?php

namespace App\Http\Controllers\Api\V1\Lender\Webhooks;

use App\Actions\Contracts\CreateWebhook;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Webhooks\StoreWebhookRequest;
use App\Transformers\WebhookTransformer;

class WebhookController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreWebhookRequest $request, CreateWebhook $createWebhook)
    {
        return fractal($createWebhook->handle($request->validated()), new WebhookTransformer())
            ->parseIncludes(['id', 'url', 'type'])
            ->respond();
    }
}
