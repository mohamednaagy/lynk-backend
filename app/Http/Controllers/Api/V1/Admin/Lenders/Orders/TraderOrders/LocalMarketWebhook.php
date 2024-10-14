<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Actions\Contracts\Orders\LocalMarketWebhook as LocalMarketWebhookInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\LocalMarketWebhookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LocalMarketWebhook extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Cancel])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  LocalMarketWebhookInterface  $webhook  ,
     */
    public function __invoke(
        LocalMarketWebhookRequest $request,
        LocalMarketWebhookInterface $webhook,
    ): JsonResponse {
        return DB::transaction(function () use ($request, $webhook) {
            $data = $request->validated();
            $webhook->handle($data);

            return $this->successResponse();
        });
    }
}
