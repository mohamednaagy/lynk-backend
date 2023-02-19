<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Actions\Contracts\Orders\GetOrderAndTraderOrderLockedForUpdate;
use App\Actions\Contracts\Orders\TraderOrders\MurabhaCompleteDocument\HandleMurabhaCompleteDocument;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\UpdateMurabhaCompleteDocumentRequest;
use App\Models\TraderOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UpdateMurabhaCompleteDocument extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  UpdateMurabhaCompleteDocumentRequest  $request
     * @param  int  $order
     * @param  TraderOrder  $traderOrder
     * @return JsonResponse
     *
     * @throws \Throwable
     */
    public function __invoke(
        UpdateMurabhaCompleteDocumentRequest $request,
        int $order,
        int $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($request, $order, $traderOrder) {
            [$order, $traderOrder] = app(GetOrderAndTraderOrderLockedForUpdate::class)->handle($traderOrder);

            $traderOrder->ensureCanAccessStep(
                FinancingOrderStatus::MurabhaOfferIssued
            );

            app(HandleMurabhaCompleteDocument::class)->handle($request, $order, $traderOrder);

            return $this->successResponse();
        });
    }
}
