<?php

namespace App\Support\QueryScoper\Scopes\Notifications;

use App\Support\QueryScoper\QueryScoper;
use Illuminate\Support\Facades\Validator;

class RecentNotificationsScope extends QueryScoper
{
    public function prepareBuilder($builder, $data)
    {
        return $builder->where(
            'created_at',
            '>=',
            now()->subDays(config('notifications.panel.days'))
        );
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
