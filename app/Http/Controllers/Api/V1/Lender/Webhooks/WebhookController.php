<?php

namespace App\Http\Controllers\Api\V1\Lender\Webhooks;

use App\Actions\Contracts\Webhooks\CreateWebhook;
use App\Actions\Contracts\Webhooks\UpdateWebhookSecretKey;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Webhooks\StoreWebhookRequest;
use App\Transformers\CompanyTransformer;
use App\Transformers\WebhookTransformer;
use Illuminate\Http\JsonResponse;

class WebhookController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::LenderWebhooks, Action::Create, Action::Manage])
        )
            ->only('store');

        $this->middleware(
            'permission:'.
                   perm(Area::Lender, [Subject::LenderWebhooks, Action::Refresh, Action::Manage])
        )
            ->only('refreshSecret');
    }

    /**
     * Handle the incoming request.
     *
     * @param  StoreWebhookRequest  $request
     * @param  CreateWebhook  $createWebhook
     * @return JsonResponse
     */
    public function store(StoreWebhookRequest $request, CreateWebhook $createWebhook): JsonResponse
    {
        $webhook = $createWebhook->handle($request->validated());

        return fractal($webhook, new WebhookTransformer())
            ->parseIncludes(['id', 'url', 'type'])
            ->respond();
    }

    /**
     * @param  UpdateWebhookSecretKey  $updateWebhookSecretKey
     * @return JsonResponse
     */
    public function refreshSecret(UpdateWebhookSecretKey $updateWebhookSecretKey): JsonResponse
    {
        $company = tenant();

        $company = $updateWebhookSecretKey->handle($company);

        return fractal($company, new CompanyTransformer())
            ->parseIncludes(['webhook_secret_key'])
            ->respond();
    }
}
