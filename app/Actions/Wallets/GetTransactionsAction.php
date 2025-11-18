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
    private ?Builder $query = null;

    private ?Company $company = null;

    private array $filters = [];

    public function handle(): mixed
    {
        $this->initQuery();

        if (! $this->query) {
            throw new \Exception('Unable to initialize query. Company must be set before calling handle().');
        }

        $this->query = $this->filterQuery($this->query, $this->filters);

        return $this->query->latest('id');
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
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

    public function attachZatcaInvoicesMedia(): self
    {
        $this->initQuery();
        if ($this->query) {
            $this->query->with([
                'media' => fn ($query) => $query->whereIn('collection_name', [
                    TransactionMediaCollection::VoucherReceipt,
                    TransactionMediaCollection::ZatcaInvoice,
                ]),
            ]);
        }

        return $this;
    }

    private function initQuery()
    {
        if (! $this->query && $this->company) {
            $this->query = $this->company->transactions(WalletType::CompanyWallet);
        }
    }
}
