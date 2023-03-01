<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Actions\Contracts\Orders\MakeOrderProceed as MakeOrderProceedInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\Lenders\Orders\MakeOrderProceedRequest;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MakeOrderProceed extends Controller
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(
        MakeOrderProceedRequest $request,
        MakeOrderProceedInterface $makeOrderProceed,
        Company $lender,
        FinancingOrder $order,
        int $traderOrder,
    ): JsonResponse {
        return DB::transaction(function () use ($request, $traderOrder, $makeOrderProceed, $order) {
            $traderOrder = TraderOrder::lockForUpdate()->findOrFail($traderOrder);

            if (
                $order->is_verification_required === false
                && $clientWakala = $request->validated('client_wakala')
            ) {
                $makeOrderProceed->setSignedClientWakala($clientWakala);
            }

            $makeOrderProceed->handle(
                $traderOrder,
                $request->validated('case'),
                true
            );

            return $this->successResponse();
        });
    }
}
