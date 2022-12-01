<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CanCreateOrder;
use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Actions\Contracts\Wallets\DeductVatPercentage;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\CreateOrderWithoutVerificationRequest;
use App\Transformers\FinancingOrderTransformer;
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(
        CreateOrderWithoutVerificationRequest $request,
        CreateFinancingOrder $createFinancingOrder,
        GenerateClientWakala $generateWakala,
        DeductVatPercentage $deductOrderCreationFee,
        CanCreateOrder $canCreateOrder
    ) {
        return DB::multipleTransaction(
            function () use ($request, $createFinancingOrder, $generateWakala, $deductOrderCreationFee, $canCreateOrder) {
                $company = tenant();
                // throw exception is balance not enough
                $canCreateOrder->handle($company);

                $financingOrder = $createFinancingOrder->handle(
                    $company,
                    array_merge(
                        $request->validated(),
                        [
                            'status' => FinancingOrderStatus::WaitingClientWakala,
                            'creator_id' => $request->user()->id,
                            'creator_type' => $request->user()->getMorphClass(),
                            'approved_at' => now(),
                            'is_verification_required' => false,
                        ]
                    )
                );

                // deduct the cost from the wallet
                $deductOrderCreationFee->handle($financingOrder);

                $generateWakala->handle($financingOrder);

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
