<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\SellingCommodity\UpdateDocumentRequest;
use App\Models\Company;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\InvalidBase64Data;

class UpdateSellingCommodity extends Controller
{
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
    public function __invoke(UpdateDocumentRequest $request, Company $lender, FinancingOrder $order): JsonResponse
    {
        if (! $order->status->is(FinancingOrderStatus::CommoditySoldToCustomer)) {
            return $this->errorResponse('order not achieved this status yet');
        }

        $order->addMediaFromBase64(
            base64_encode(file_get_contents($request->file('document')))
        )->usingFileName($order->getMedia())
            ->toMediaCollection(FinancingOrderMediaCollection::SellingCommodityToCustomer);

        return $this->successResponse([]);
    }
}
