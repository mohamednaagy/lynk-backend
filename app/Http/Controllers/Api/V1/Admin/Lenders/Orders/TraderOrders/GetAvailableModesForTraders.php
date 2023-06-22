<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GetAvailableModesForTraders extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Show, Action::Manage])
        );
    }

    public function __invoke(): JsonResponse
    {
        $availableModes = collect(config('trader.providers', []))
            ->transform(function ($trader) {
                return $trader['modes'];
            })
            ->toArray();

        return $this->successResponse($availableModes);
    }
}
