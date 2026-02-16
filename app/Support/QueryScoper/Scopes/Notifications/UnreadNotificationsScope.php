<?php

namespace App\Support\QueryScoper\Scopes\Notifications;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

class UnreadNotificationsScope extends QueryScoper
{
    public function prepareBuilder($builder, $data): Builder
    {
        return $builder->whereNull('read_at');
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
