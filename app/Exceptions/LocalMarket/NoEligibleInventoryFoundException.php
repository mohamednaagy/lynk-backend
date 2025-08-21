<?php

namespace App\Exceptions\LocalMarket;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NoEligibleInventoryFoundException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @return \Illuminate\Http\Response
     */
    public function render(Request $request)
    {
        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info('can not find good inventories ');
    }
}
