<?php

namespace App\Support\QueryScoper\Scopes\Company;

use App\Enums\CompanyMarketType;
use App\Support\QueryScoper\QueryScoper;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class CompanyMarketTypeScope extends QueryScoper
{
    public function prepareData(): array
    {
        $v = Request::query('market_type');
        $types = is_array($v) ? $v : (is_null($v) ? null : [$v]);

        return [
            'market_type' => $types,
        ];
    }

    public function validator($data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, [
            'market_type' => ['required', 'array'],
            'market_type.*' => [new EnumValue(CompanyMarketType::class, false)],
        ]);
    }

    public function prepareBuilder($builder, $data): Builder
    {
        $values = $data['market_type'];

        return $builder->whereHas('lenderDetail', function (Builder $q) use ($values) {
            $q->whereIn('preferred_market_type', $values);
        });
    }
}
