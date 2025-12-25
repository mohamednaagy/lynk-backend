<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\Lender;
use App\Models\TraderOrder;
use Illuminate\Http\JsonResponse;

class GetCommodityCertificateForClient extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Show, Action::Manage])
        );
    }

    public function __invoke(Lender $lender, int $order, TraderOrder $traderOrder): JsonResponse
    {
        $media = $traderOrder->getFirstMedia(TraderOrderMediaCollection::SellingCommodityToCustomer);

        return $this->successResponse([
            'url' => $media?->file_url,
        ]);
    }
}
