<?php

namespace App\Exceptions\BURSAM;

use App\Enums\ErrorCode;
use Exception;

class BursamRequiredConfigException extends Exception
{
    public function __construct(protected string $configKey, ?string $customMessage = null)
    {
        $message = $customMessage ?? "Bursam config '{$configKey}' is not set";
        parent::__construct($message, ErrorCode::BURSAM_REQUIRED_CONFIG_NOT_SET);
    }

}
