<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\DirectOrderToCommodityTradingRequest;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Http\Request;

class DirectOrderToCommodityTrading extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(DirectOrderToCommodityTradingRequest $request, CreateFinancingOrder $createFinancingOrder)
    {
        return app(DatabaseServiceInterface::class)->transaction(
            static function () use ($createFinancingOrder, $request) {
                $financingOrder = $createFinancingOrder->handle(
                    array_merge(
                        $request->validated(),
                        [
                            'status' => FinancingOrderStatus::InProgress,
                            'creator_id' => auth()->id(),
                            'creator_type' => auth()->user()->getMorphClass(),
                            'approved_at' => now(),
                        ]
                    )
                );

                // -1 generate wakala
                // -2 start commodity trading process

                return fractal($financingOrder, new FinancingOrderTransformer())->respond();
            }
        );
    }
}
