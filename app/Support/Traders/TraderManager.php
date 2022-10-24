<?php

namespace App\Support\Traders;

use Illuminate\Support\Manager;

class TraderManager extends Manager
{
    public function getDefaultDriver()
    {
        return env('DEFAULT_TRADER');
    }
}
