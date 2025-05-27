<?php

namespace App\Http\Controllers\Api\V1\Admin\TraderProducts;

use App\Actions\Contracts\TraderProduct\BuildTraderProductsQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Enums\Trader;
use App\Enums\TraderProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\TraderProducts\ListTraderProductsRequest;
use App\Transformers\TraderProductTransformer;
use Illuminate\Http\JsonResponse;

class TraderProductsLiteList extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::TraderProducts, Action::Index, Action::Manage])
        );
    }

    /**
     * Handle the incoming request to list trader products.
     */
    public function __invoke(
        ListTraderProductsRequest $request,
        BuildTraderProductsQuery $buildTraderProductsQuery
    ): JsonResponse {
        $status = $request->input('status', $request->defaults()['status']);
        $provider = $request->input('provider', $request->defaults()['provider']);

        $products = $buildTraderProductsQuery
            ->setStatus($status ? TraderProductStatus::fromValue($status) : null)
            ->setProvider($provider ? Trader::fromValue($provider) : null)
            ->handle()
            ->get(['id', 'name', 'code']);

        return fractal($products, new TraderProductTransformer)
            ->parseIncludes(['id', 'name', 'code'])
            ->respond();
    }
}
