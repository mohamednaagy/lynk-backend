<?php

namespace App\Exceptions\LocalMarket;

use Exception;

class NoActiveSpecializedEntityException extends Exception
{
    public function __construct()
    {
        parent::__construct('No active Specialized Entity found. At least one active Specialized Entity must exist to process sell orders.');
    }
}
