<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GetTradersWithAvailableModes extends Controller
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
            ->map(function ($traderData, $traderName) {
                return [
                    'trader' => $traderName,
                    'modes' => $traderData['modes'],
                ];
            })
            ->values()
            ->toArray();

        return $this->successResponse($availableModes);
    }
}
