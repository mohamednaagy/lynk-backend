<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\FinancingOrder;
use Illuminate\Support\Arr;
use Propaganistas\LaravelPhone\PhoneNumber;

class CreateFinancingOrderAction implements CreateFinancingOrder
{
    public function __construct(protected CreateTransactions $createTransactions)
    {
    }

    public function handle(array $data): FinancingOrder
    {
        $data['phone_number'] = PhoneNumber::make($data['phone_number'], $data['phone_country_code']);
        $financingOrder = FinancingOrder::create(
            Arr::only($data, [
                'reference_number',
                'national_id',
                'phone_number',
                'amount',
                'selling_price',
                'status',
                'creator_id',
                'creator_type',
                'approved_at',
            ])
        );

        $company = tenant();
        $this->createTransactions->handle(
            $company->getWallet(WalletType::CompanyWallet),
            TransactionReason::OrderCreationFee,
            $company->order_cost,
            [
                'financing_order_id' => $financingOrder->id,
                'reference_number ' => $financingOrder->reference_number,
                'amount' => $financingOrder->amount,
            ]
        );

        return $financingOrder;
    }
}
