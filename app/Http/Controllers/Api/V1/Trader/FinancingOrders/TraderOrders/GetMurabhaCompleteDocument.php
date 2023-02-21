<?php

namespace App\Http\Controllers\Api\V1\Trader\FinancingOrders\TraderOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\TraderOrder;
use Illuminate\Http\JsonResponse;

class GetMurabhaCompleteDocument extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Trader, [Subject::FinancingOrders, Action::Show, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  int  $order
     * @param  TraderOrder  $traderOrder
     * @return JsonResponse
     */
    public function __invoke(
        int $order,
        TraderOrder $traderOrder
    ): JsonResponse {
        $media = $traderOrder->getFirstMedia(TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo);

        return $this->successResponse(['url' => $media->fileUrl ?? null]);
    }
}
