<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CompleteOrder as CompleteOrderInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\Subject;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\CompleteOrderRequest;
use App\Models\TraderOrder;
use Illuminate\Support\Facades\DB;

class CompleteOrder extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::Lender, [Subject::FinancingOrders, Action::Edit, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(
        CompleteOrderRequest $request,
        CompleteOrderInterface $completeOrder,
        int $order,
    ) {
        return DB::transaction(
            function () use ($request, $completeOrder, $order) {
                $traderOrder = TraderOrder::query()
                    ->where('financing_order_id', $order)
                    ->whereHas('traderHistories', function ($query) {
                        $query->where('action', FinancingOrderHistory::MurabahaSaleCompleted);
                    })
                    ->first();

                if (! $traderOrder) {
                    throw new OrderStatusDoesNotFollowSequenceException;
                }

                $paymentProofMedia = $completeOrder->handle($traderOrder->id, $request->validated());

                return $this->successResponse([
                    'payment_proof_url' => $paymentProofMedia?->file_url,
                ]);
            }
        );
    }
}
