<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Companies\GetFinancialProduct;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CommodityTypeStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FinancingProductsLiteList extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.perm(Area::Lender, [Subject::FinancialProducts, Action::Manage, Action::Index])
        );
    }

    /**
     * Handle the incoming request.
     */
    public function index(GetFinancialProduct $getFinancialProducts): JsonResponse
    {
        $company = auth()->user()->company;
        $financialProducts = $getFinancialProducts
            ->handle($company);

            return $this->successResponse(['allowed_financing_products' => $financialProducts->toArray()]);
        }
}
