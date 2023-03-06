<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Actions\Contracts\Orders\UpdateOrderPaymentProof as UpdateOrderPaymentProofInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\UpdateOrderPaymentProofRequest;
use Illuminate\Support\Facades\DB;

class UpdateOrderPaymentProof extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(
        UpdateOrderPaymentProofRequest $request,
        UpdateOrderPaymentProofInterface $updateOrderPaymentProof,
        int $order,
    ) {
        return DB::transaction(
            function () use ($request, $updateOrderPaymentProof, $order) {
                $paymentProofMedia = $updateOrderPaymentProof->handle($order, $request->validated());

                return $this->successResponse([
                    'payment_proof_url' => $paymentProofMedia?->file_url,
                ]);
            }
        );
    }
}
