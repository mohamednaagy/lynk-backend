<?php

namespace App\Support\Traders;

use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Drivers\BursamDriver;
use App\Support\Traders\Drivers\DmccDriver;
use App\Support\Traders\Drivers\FakeDriver;
use Illuminate\Support\Manager;

class TraderManager extends Manager
{
    public function getDefaultDriver()
    {
        return config('trader.default', 'dmcc');
    }

    public function createDmccDriver(): TraderInterface
    {
        return new DmccDriver();
    }

    public function createBursamDriver(): TraderInterface
    {
        return new BursamDriver();
    }

    public function createFakeDriver(): TraderInterface
    {
        return new FakeDriver();
    }
}
