<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Http\JsonResponse;

class GetMurabhaCompleteDocument extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Show, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  Company  $lender
     * @param  FinancingOrder  $order
     * @param  TraderOrder  $traderOrder
     * @return JsonResponse
     */
    public function __invoke(
        Company $lender,
        FinancingOrder $order,
        TraderOrder $traderOrder
    ): JsonResponse {
        $media = $traderOrder->getMedia(TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo)
            ->first();

        return $this->successResponse(['url' => $media->fileDownloadableUrl ?? null]);
    }
}
