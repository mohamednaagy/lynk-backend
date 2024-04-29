<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\GetTransactions;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\WalletType;
use App\Models\Company;
use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Builder;

class GetTransactionsAction implements GetTransactions
{
    public function handle(Company $company, array $data): mixed
    {
        $query = $company->transactions(WalletType::CompanyWallet);
        $newQuery = $this->filterQuery($query, $data);

        return $newQuery->with([
            'media' => fn ($query) => $query->whereIn('collection_name', [
                TransactionMediaCollection::VoucherReceipt,
                TransactionMediaCollection::ZatcaInvoice,
            ]),
        ])
            ->latest('id')
            ->paginate();
    }

    public function filterQuery(Builder $builder, array $data): Builder
    {

        // TODO: handle subtract in function

        /**
         *  Note
         *  All numbers are rounded up by money package
         *  So we Subtract 50 from the amount to get the smallest number that can be rounded to use in the filter
         *  Ex if I need to search 50.25 this number will be converted in the package to 502500
         *  This number will be returned from the database  any numbers like 502500, 502499, or 502489 .... etc to 502450 because all numbers equal 502500 after rounded
         */
        $amountLTE = isset($data['amount_lte'])
            ? Money::parseByDecimal($data['amount_lte'], Money::getDefaultCurrency())->getAmount()
            : null;

        $amountGTE = isset($data['amount_gte'])
            ? Money::parseByDecimal($data['amount_gte'], Money::getDefaultCurrency())->getAmount() - 50
            : null;

        if ($amountLTE) {
            $builder->where('amount', '<=', $amountLTE);
        }

        if ($amountGTE) {
            $builder->where('amount', '>=', $amountGTE);
        }

        if (isset($data['date_from'])) {
            $builder->whereDate('created_at', '>=', $data['date_from']);
        }

        if (isset($data['date_to'])) {
            $builder->whereDate('created_at', '<=', $data['date_to']);
        }

        return $builder;
    }
}
