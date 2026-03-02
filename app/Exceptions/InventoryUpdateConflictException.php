<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class InventoryUpdateConflictException extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::INVENTORY_NOT_UPDATABLE;
    }

    protected function errorMessage(): string
    {
        return __('error.inventory_update_conflict');
    }
}
