<?php

namespace App\Support\QueryScoper\Scopes\Company;

use App\Enums\TraderOrderMode;
use App\Support\QueryScoper\QueryScoper;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class CompanyTradingModeScope extends QueryScoper
{
    public function prepareData(): array
    {
        return [
            'trading_mode' => Request::query('trading_mode'),
        ];
    }

    public function validator($data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, [
            'trading_mode' => ['required', new EnumValue(TraderOrderMode::class, false)],
        ]);
    }

    public function prepareBuilder($builder, $data): Builder
    {
        return $builder->whereHas('lenderDetail', function (Builder $q) use ($data) {
            $q->where('trading_mode', $data['trading_mode']);
        });
    }
}
