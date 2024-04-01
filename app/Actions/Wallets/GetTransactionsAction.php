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

        $amountLTE = isset($data['amount_lte'])
            ? Money::parseByDecimal($data['amount_lte'], Money::getDefaultCurrency())->getAmount()
            : null;

        $amountGTE = isset($data['amount_gte'])
            ? Money::parseByDecimal($data['amount_gte'], Money::getDefaultCurrency())->getAmount()
            : null;

        if ($amountLTE && $amountGTE) {
            $builder->whereBetween('amount', [$amountGTE, $amountLTE]);
        } elseif ($amountLTE) {
            $builder->where('amount', '<=', $amountLTE);
        } elseif ($amountGTE) {
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
