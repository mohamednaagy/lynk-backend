<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\Commodities;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Traders\Commodities\StoreProductRequest;
use App\Http\Requests\V1\Admin\Traders\Commodities\UpdateProductRequest;
use App\Http\Resources\OpenMarketProductResource;
use App\Models\OpenMarketProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OpenMarketProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $products = OpenMarketProduct::all()->groupBy('provider');

        return OpenMarketProductResource::collection($products);
    }

    public function show(OpenMarketProduct $openMarketProduct): OpenMarketProductResource
    {
        return new OpenMarketProductResource($openMarketProduct);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = OpenMarketProduct::query()->create($request->validated());

        return $this->successResponse([]);
    }

    public function update(UpdateProductRequest $request, OpenMarketProduct $openMarketProduct): JsonResponse
    {
        $product = $openMarketProduct->update($request->validated());

        return $this->successResponse([]);
    }

    public function destroy(OpenMarketProduct $openMarketProduct): JsonResponse
    {
        $openMarketProduct->delete();

        return $this->successResponse([]);
    }
}
