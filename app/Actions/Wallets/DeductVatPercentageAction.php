<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\DeductVatPercentage;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Models\FinancingOrder;
use App\Support\Money\Money;

class DeductVatPercentageAction implements DeductVatPercentage
{
    public function __construct(
        protected CreateTransactions $createTransactions,
        protected GetProjectSettings $getProjectSettings
    ) {
    }

    public function handle(FinancingOrder $financingOrder)
    {
        $company = tenant();
        $vatPercentageFee = Money::SAR($financingOrder->amount)->multiply($this->getProjectSettings->handle()->getVatRate());

        $this->createTransactions->handle(
            $company->getWallet(WalletType::CompanyWallet),
            \money($vatPercentageFee, 'SAR'),
            TransactionReason::VatPercentageFee,
            [
                'financing_order_id' => $financingOrder->id,
                'reference_number ' => $financingOrder->reference_number,
                'amount' => $vatPercentageFee,
                'description' => __('transaction-description.vat_percentage', [
                    'orderId' => $financingOrder->id ?? '',
                    'percentage' => $this->getProjectSettings->handle()->getVatRateInPercentage() ?? '',
                ]),
            ]
        );
    }
}
