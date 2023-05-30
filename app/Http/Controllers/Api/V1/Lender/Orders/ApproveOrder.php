<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\ApproveOrder as ApproveOrderInterface;
use App\Actions\Contracts\Wallets\DeductOrderCreationFee;
use App\Actions\Contracts\Wallets\DeductVatPercentage;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\FinancingOrder;
use App\Notifications\OrderApproved;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ApproveOrder extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.
            perm(Area::Lender, [Subject::FinancingOrders, Action::Approve])
        );
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(
        Request $request,
        ApproveOrderInterface $approveOrder,
        int $order,
        DeductOrderCreationFee $deductOrderCreationFee,
        DeductVatPercentage $deductVatPercentage,
        GenerateZatcaInvoice $generateFatoura): JsonResponse
    {
        return DB::transaction(function () use (
            $request,
            $approveOrder,
            $order,
            $deductOrderCreationFee,
            $deductVatPercentage,
            $generateFatoura) {
            $order = FinancingOrder::lockForUpdate()->findOrFail($order);

            if ($order->status->cantMoveTo(FinancingOrderStatus::Approved)) {
                return $this->errorResponse(
                    __('error.order_cannot_be_approved_because_it_is_approved'),
                    Response::HTTP_BAD_REQUEST,
                    ErrorCode::ORDER_ALREADY_APPROVED
                );
            }

            // deduct the cost from the wallet
            $creationFeeTransaction = $deductOrderCreationFee->handle($order);
            $deductVatPercentage->handle($order, $creationFeeTransaction, tenant());

            $generateFatoura->handel(
                $order,
                creationFeeTransaction: $creationFeeTransaction
            );

            $approveOrder->handle($order, $request->user());

            if (! $order->creator->is($request->user())) {
                $order->creator->notify(
                    new OrderApproved($order, $request->user(), now())
                );
            }

            return $this->successResponse();
        });
    }
}
