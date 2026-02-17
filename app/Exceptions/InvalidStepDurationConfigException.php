<?php

declare(strict_types=1);

namespace App\Exceptions;

use InvalidArgumentException;

class InvalidStepDurationConfigException extends InvalidArgumentException
{
    public static function missingRequiredKeys(
        string $provider,
        string $version,
        int $contractSignedType,
        int $endHistoryAction
    ): self {
        return new self(
            "Step definition is invalid for provider: {$provider}, version: {$version}, "
                ."contract signed type: {$contractSignedType}, end history action: {$endHistoryAction}. "
                .'Must have step, start_history, end_history.'
        );
    }
}
