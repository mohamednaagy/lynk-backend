<?php

namespace App\Support\QueryScoper\Scopes\Admin\BankAccounts;

use App\Models\AuthorizedEntity;
use App\Models\User;
use App\Support\QueryScoper\QueryScoper;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class AccountableScope extends QueryScoper
{
    protected $typeModelMap = [
        'ap' => AuthorizedEntity::class,
        'inv' => User::class,
    ];

    public function prepareBuilder($builder, $data)
    {
        $segments = explode('-', $data['accountable']);

        $modelClass = $this->typeModelMap[$segments[0]];

        $morphClass = (new $modelClass)->getMorphClass();

        return $builder->where('accountable_type', $morphClass)
            ->when(isset($segments[1]) && $segments[1], function ($query) use ($segments) {
                return $query->where('accountable_id', $segments[1]);
            });
    }

    public function prepareData()
    {
        return [
            'accountable' => Request::query('accountable'),
        ];
    }

    public function validator($data)
    {
        return Validator::make($data, [
            'accountable' => ['required', 'string', 'regex:/^(?:inv|ap)(?:-[1-9]\d{1,})?/'],
        ]);
    }
}
