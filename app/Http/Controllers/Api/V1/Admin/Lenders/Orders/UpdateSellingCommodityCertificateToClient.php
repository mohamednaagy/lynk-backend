<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\UpdateSellingCommodityToClientRequest;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\InvalidBase64Data;

class UpdateSellingCommodityCertificateToClient extends Controller
{
    use TraderHelperTrait;

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
     * @param  UpdateDocumentRequest  $request
     * @param  Company  $lender
     * @param  FinancingOrder  $order
     * @return JsonResponse
     *
     * @throws FileCannotBeAdded
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws InvalidBase64Data
     */
    public function __invoke(
        UpdateSellingCommodityToClientRequest $request,
        Company $lender,
        int $order,
        TraderOrder $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($request, $order, $traderOrder) {
            $order = FinancingOrder::lockForUpdate()->findOrFail($order);

            $trader = Trader::driver($traderOrder->drive);

            if ($request->validated('automatically_generate_file')) {
                $trader->createSellingCommodityToCustomerDocument($traderOrder);
            } else {
                $traderOrder->addMediaFromBase64(
                    base64_encode(file_get_contents($request->file('document')))
                )
                    ->usingFileName('selling-commodity-to-customer.pdf')
                    ->toMediaCollection(TraderOrderMediaCollection::SellingCommodityToCustomer);

                $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateSellingCommodityToCustomerDocument);
            }

            $trader->updateOrderStatus($order, FinancingOrderStatus::CommoditySoldToCustomer);

            return $this->successResponse([
                'url' => $traderOrder
                    ->getFirstMedia(TraderOrderMediaCollection::SellingCommodityToCustomer)
                    ?->fileDownloadableUrl ?? null,
            ]);
        });
    }
}
