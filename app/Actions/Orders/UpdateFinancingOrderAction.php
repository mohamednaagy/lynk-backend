<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\UpdateFinancingOrder;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use Cknow\Money\Money;
use Illuminate\Support\Arr;
use Propaganistas\LaravelPhone\PhoneNumber;

class UpdateFinancingOrderAction implements UpdateFinancingOrder
{
    /**
     * @param  mixed  $data
     * @return mixed
     */
    public function handle(FinancingOrder $financingOrder, array $data): FinancingOrder
    {
        if (isset($data['phone_number'])) {
            $data['phone_number'] = PhoneNumber::make($data['phone_number'], $data['phone_country_code']);
        }

        if (isset($data['amount'])) {
            $data['amount'] = Money::parseByDecimal($data['amount'], $financingOrder->currency);
        }

        if (isset($data['selling_price'])) {
            $data['selling_price'] = Money::parseByDecimal($data['selling_price'], $financingOrder->currency);
        }

        if ($financingOrder->status->is(FinancingOrderStatus::Rejected)) {
            $data['status'] = $financingOrder->company()->withTrashed()->first()->does_order_require_approval
                ? FinancingOrderStatus::PendingApproval
                : FinancingOrderStatus::PendingTraderOrder;

            if ($data['status'] !== FinancingOrderStatus::PendingApproval) {
                $financingOrder->approved_at = now();
            }
        }

        $data['status_reason'] = null;

        $financingOrder->update(
            Arr::only(
                $data,
                [
                    'reference_number',
                    'national_id',
                    'phone_number',
                    'amount',
                    'selling_price',
                    'status',
                    'status_reason',
                    'customer_name',
                    'is_verification_required',
                ]
            )
        );

        return $financingOrder;
    }
}
