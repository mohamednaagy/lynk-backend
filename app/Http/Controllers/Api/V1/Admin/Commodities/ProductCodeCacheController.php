<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ProductCodeCacheController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit, Action::Manage])
        )
            ->only(['index', 'delete']);
    }

    public function index(): JsonResponse
    {
        $unavailableProductCodes = Cache::get('bursam_unavailable_product_codes', []);

        return $this->successResponse($unavailableProductCodes);
    }

    public function delete(): JsonResponse
    {
        Cache::delete('bursam_unavailable_product_codes');

        return $this->successResponse([]);
    }
}
