<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Http\JsonResponse;

class GetSellingCommodityCertificateToClient extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Show, Action::Manage])
        );
    }

    /**
     * @param  Company  $lender
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function __invoke(Company $lender, int $order, TraderOrder $traderOrder): JsonResponse
    {
        $media = $traderOrder->getFirstMedia(TraderOrderMediaCollection::SellingCommodityToCustomer);

        return $this->successResponse([
            'url' => $media?->fileDownloadableUrl,
        ]);
    }
}
