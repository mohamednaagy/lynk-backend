<?php

namespace App\Exceptions\LocalMarket;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JobStatusException extends Exception
{
    public function __construct(string $message, private string $customMessage, private int $localMarketOrderID)
    {
        parent::__construct($message, 0, null);
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @return \Illuminate\Http\Response
     */
    public function render(Request $request)
    {
        $errorMessage = $this->customMessage.', for the given order: '.$this->localMarketOrderID;
        Log::channel('local_market')->error($errorMessage,
            ['message' => $this->message]);
    }
}
