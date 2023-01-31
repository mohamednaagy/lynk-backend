<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;

class GetMurabhaCompleteDocument extends Controller
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
     * @param  Company  $lender
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function __invoke(Company $lender, FinancingOrder $order): JsonResponse
    {
        $url = get_file_url(
            $order->getMedia(FinancingOrderMediaCollection::WarrantAmendmentExceptWarrantNo)
                ->first()
        );

        return $this->successResponse(['url' => $url]);
    }
}
