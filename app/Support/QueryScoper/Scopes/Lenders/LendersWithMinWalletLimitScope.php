<?php

declare(strict_types=1);

namespace App\Support\QueryScoper\Scopes\Lenders;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Support\Facades\Validator;

class LendersWithMinWalletLimitScope extends QueryScoper
{
    public function prepareBuilder($builder, $data)
    {
        return $builder->whereHas('lenderDetail', function ($q) {
            $q->whereNotNull('min_wallet_limit');
        });
    }

    public function prepareData()
    {
        return [];
    }

    public function validator($data)
    {
        return Validator::make($data, []);
    }
}
