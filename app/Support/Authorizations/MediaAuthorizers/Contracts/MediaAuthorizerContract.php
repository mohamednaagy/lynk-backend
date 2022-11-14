<?php

namespace App\Support\Authorizations\MediaAuthorizers\Contracts;

interface MediaAuthorizerContract
{
    public function canAccess(): bool;
}
