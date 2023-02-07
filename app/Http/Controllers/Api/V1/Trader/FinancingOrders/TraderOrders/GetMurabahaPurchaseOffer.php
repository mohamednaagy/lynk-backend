<?php

namespace App\Http\Controllers\Api\V1\Trader\FinancingOrders\TraderOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Http\JsonResponse;

class GetMurabahaPurchaseOffer extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::FinancingOrders, Action::Show, Action::Manage])
        );
    }

    public function __invoke(
        Company $lender,
        FinancingOrder $order,
        TraderOrder $traderOrder
    ): JsonResponse {
        $url = $this->fileUrl($traderOrder->getFirstMedia(TraderOrderMediaCollection::MurabahaPurchaseOrder));

        return $this->successResponse([
            'murabaha_purchase_offer' => $url,
        ]);
    }

    public function fileUrl($media): ?string
    {
        if ($media) {
            return route('api.v1.media.download', ['media' => $media->uuid]);
        }

        return null;
    }
}
