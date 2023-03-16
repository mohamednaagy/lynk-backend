<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Actions\Contracts\Orders\CreateTrading as CreateTradingInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\CreateTradingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CreateTrading extends Controller
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
     * @return \Illuminate\Http\Response
     */
    public function __invoke(
        CreateTradingRequest $request,
        CreateTradingInterface $createTrading,
        int $order
    ): JsonResponse {
        return DB::transaction(function () use ($request, $createTrading, $order) {
            $createTrading->handle($order, $request->validated());

            return $this->successResponse();
        });
    }
}
