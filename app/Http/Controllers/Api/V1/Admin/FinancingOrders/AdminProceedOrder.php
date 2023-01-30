<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\Lenders\Orders\AdminProceedOrderRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminProceedOrder extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Proceed, Action::Index])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(
        AdminProceedOrderRequest $request,
        MakeOrderProceed $makeOrderProceed,
        Company $lender,
        int $order,
    ): JsonResponse {
        return DB::transaction(function () use ($request, $order, $makeOrderProceed) {
            return $this->successResponse($makeOrderProceed->handle($order, $request->validated('case')));
        });
    }
}
