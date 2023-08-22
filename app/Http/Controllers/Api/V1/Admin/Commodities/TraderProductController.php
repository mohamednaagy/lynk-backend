<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\StoreProductRequest;
use App\Http\Requests\V1\Admin\Commodities\UpdateProductRequest;
use App\Models\TraderProduct;
use App\Transformers\TraderProductTransformer;
use Illuminate\Http\JsonResponse;

class TraderProductController extends Controller
{
    public function index()
    {
        $products = TraderProduct::all();

        return fractal($products, new TraderProductTransformer)
            ->respond();
    }

    public function show(TraderProduct $traderProduct)
    {
        return fractal($traderProduct, new TraderProductTransformer)
            ->respond();
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        TraderProduct::query()->create($request->validated());

        return $this->successResponse([]);
    }

    public function update(UpdateProductRequest $request, TraderProduct $traderProduct): JsonResponse
    {
        $traderProduct->update($request->validated());

        return $this->successResponse([]);
    }

    public function destroy(TraderProduct $traderProduct): JsonResponse
    {
        $traderProduct->delete();

        return $this->successResponse([]);
    }
}
