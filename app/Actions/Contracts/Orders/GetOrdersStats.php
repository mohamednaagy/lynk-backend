<?php

namespace App\Actions\Contracts\Orders;

use Illuminate\Database\Eloquent\Model;

interface GetOrdersStats
{
    public function handle();

    public function setCreator(Model $creator);
}
