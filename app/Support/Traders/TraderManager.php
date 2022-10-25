<?php

namespace App\Support\Traders;

use Illuminate\Support\Manager;

class TraderManager extends Manager
{
    public function getDefaultDriver()
    {
        return config('trader.default');
    }
}
