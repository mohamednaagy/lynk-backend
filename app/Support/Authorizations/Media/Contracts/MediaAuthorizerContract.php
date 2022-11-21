<?php

namespace App\Support\Authorizations\Media\Contracts;

interface MediaAuthorizerContract
{
    public function canAccess(): bool;
}
