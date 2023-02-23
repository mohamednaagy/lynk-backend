<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Actions\Contracts\Orders\MakeOrderProceed as ProceedOrderInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\MakeOrderProceedRequest;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class MakeOrderProceed extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::Lender, [Subject::FinancingOrders, Action::Proceed, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  MakeOrderProceedRequest  $request
     * @param  AcceptClientWakala  $acceptClientWakala
     * @param  int  $order
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function __invoke(
        MakeOrderProceedRequest $request,
        ProceedOrderInterface $makeOrderProceed,
        FinancingOrder $order
    ): JsonResponse {
        return DB::transaction(function () use ($request, $order, $makeOrderProceed) {
            $this->authorize('view', $order);

            $traderOrder = $order->activeTraderOrder()->lockForUpdate()->firstOrFail();

            if ($clientWakala = $request->validated('client_wakala')) {
                $makeOrderProceed->setClientWakala($clientWakala);
            }

            $makeOrderProceedResponse = $makeOrderProceed->handle($traderOrder, $request->validated('case'));

            return $this->successResponse($makeOrderProceedResponse);
        });
    }
}
