<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class InventoryNotUpdatableException extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::INVENTORY_NOT_UPDATABLE;
    }

    protected function errorMessage(): string
    {
        return __('error.inventory_cannot_be_updated');
    }
}
