<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Actions\Contracts\Wakala\GenerateWakala;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\CreateOrderWithoutVerificationRequest;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Support\Facades\DB;

class CreateOrderWithoutVerification extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CreateOrderWithoutVerificationRequest $request, CreateFinancingOrder $createFinancingOrder, GenerateWakala $generateWakala)
    {
        DB::transaction(function () use ($createFinancingOrder, $request, $generateWakala) {
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

            $generateWakala->handle($financingOrder);
            // -2 start commodity trading process

            return fractal($financingOrder, new FinancingOrderTransformer())->respond();
        }
        );
    }
}
