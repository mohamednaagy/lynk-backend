<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CanCreateOrder;
use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Actions\Contracts\Wallets\DeductOrderCreationFee;
use App\Actions\Contracts\Wallets\DeductVatPercentage;
use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\CreateOrderWithoutVerificationRequest;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CreateOrderWithoutVerification extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::FinancingOrders, Action::Create, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  CreateOrderWithoutVerificationRequest  $request
     * @param  CanCreateOrder  $canCreateOrder
     * @param  CreateFinancingOrder  $createFinancingOrder
     * @param  DeductOrderCreationFee  $deductOrderCreationFee
     * @param  DeductVatPercentage  $deductVatPercentage
     * @param  GenerateZatcaInvoice  $generateFatoura
     * @return Response
     */
    public function __invoke(
        CreateOrderWithoutVerificationRequest $request,
        CanCreateOrder $canCreateOrder,
        CreateFinancingOrder $createFinancingOrder,
        DeductOrderCreationFee $deductOrderCreationFee,
        DeductVatPercentage $deductVatPercentage,
        GenerateZatcaInvoice $generateFatoura
    ) {
        return DB::multipleTransaction(
            function () use (
                $request,
                $createFinancingOrder,
                $deductOrderCreationFee,
                $deductVatPercentage,
                $canCreateOrder,
                $generateFatoura
            ) {
                $company = tenant();
                // throw exception is balance not enough
                $canCreateOrder->handle($company);

                $financingOrder = $createFinancingOrder->handle(
                    $company,
                    array_merge(
                        $request->validated(),
                        [
                            'status' => FinancingOrderStatus::Approved,
                            'creator_id' => $request->user()->id,
                            'creator_type' => $request->user()->getMorphClass(),
                            'approved_at' => now(),
                            'is_verification_required' => false,
                        ]
                    )
                );

                // deduct the cost from the wallet
                $creationFeeTransaction = $deductOrderCreationFee->handle($financingOrder);
                $vatPercentageTransaction = $deductVatPercentage->handle($financingOrder, $creationFeeTransaction, $company);

                $generateFatoura->handel(
                    $financingOrder,
                    creationFeeTransaction: $creationFeeTransaction,
                    vatPercentageTransaction: $vatPercentageTransaction
                );

                return fractal($financingOrder, new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'national_id',
                        'amount',
                        'selling_price',
                        'is_approved',
                        'status_reason',
                        'phone_country_code',
                        'phone_number',
                        'phone_number_formatted',
                    ])->respond();
            }
        );
    }
}
