<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Enums\FinancingOrderStatus;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Exceptions\BalanceIsNotEnoughException;
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
    public function __invoke(
        CreateOrderWithoutVerificationRequest $request,
        CreateFinancingOrder $createFinancingOrder,
        GenerateClientWakala $generateWakala,
        CreateTransactions $createTransactions
    ) {
        return DB::transaction(
            function () use ($createFinancingOrder, $request, $generateWakala, $createTransactions) {
                $company = tenant();
                $wallet = $company->getWallet(WalletType::CompanyWallet);
                // throw exception is balance not enough
                if ($wallet->balance < tenant()->order_cost) {
                    throw new BalanceIsNotEnoughException();
                }

                $financingOrder = $createFinancingOrder->handle(
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

                $generateWakala->handle($financingOrder);

                // deduct the cost from the wallet
                $createTransactions->handle(
                    $wallet,
                    TransactionReason::OrderCreationFee,
                    $company->order_cost,
                    [
                        'financing_order_id' => $financingOrder->id,
                        'reference_number ' => $financingOrder->reference_number,
                        'amount' => $financingOrder->amount,
                        'order_cost' => $financingOrder->order_cost,
                    ]
                );

                return fractal($financingOrder, new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'status',
                        'company_id',
                        'reference_number',
                        'national_id',
                        'amount',
                        'selling_price',
                        'contract',
                        'power_of_attorney',
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
