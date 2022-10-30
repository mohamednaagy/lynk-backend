<?php

namespace App\Actions\Contracts\Orders;

use Illuminate\Database\Eloquent\Model;

interface GetOrdersStatsByCreator
{
    public function handle(Model $model);
}
