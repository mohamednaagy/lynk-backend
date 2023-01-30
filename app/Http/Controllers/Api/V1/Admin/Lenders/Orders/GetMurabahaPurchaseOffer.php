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

class GetMurabahaPurchaseOffer extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Manage])
        );
    }

    public function __invoke(
        Company $lender,
        FinancingOrder $order
    ): JsonResponse {
        $url = $this->fileUrl($order->getMedia(FinancingOrderMediaCollection::MurabahaPurchaseOrder)->first());

        return $this->successResponse([$url]);
    }

    public function fileUrl($media): ?string
    {
        if ($media) {
            return route('api.v1.media.download', ['media' => $media->uuid]);
        }

        return null;
    }
}
