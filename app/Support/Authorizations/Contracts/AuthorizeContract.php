<?php

namespace App\Support\Authorizations\Contracts;

interface AuthorizeContract
{
    public function canAccess(): bool;
}
