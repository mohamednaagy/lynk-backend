<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\MurabhaCompleteDocument;

use App\Actions\Contracts\Orders\TraderOrders\UpdateMurabhaCompleteDocument as UpdateMurabhaCompleteDocumentInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\MurabhaCompleteDocument\UpdateDocumentRequest;
use App\Models\Company;
use App\Models\TraderOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UpdateMurabhaCompleteDocument extends Controller
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
     * @param  UpdateDocumentRequest  $request
     * @param  Company  $lender
     * @param  int  $order
     * @param  TraderOrder  $traderOrder
     * @return JsonResponse
     */
    public function __invoke(
        UpdateDocumentRequest $request,
        Company $lender,
        int $order,
        TraderOrder $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($request, $order, $traderOrder) {
            app(UpdateMurabhaCompleteDocumentInterface::class)->handle($request, $order, $traderOrder);

            return $this->successResponse();
        });
    }
}
