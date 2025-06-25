<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use SoftDeletes;

    protected $casts = [
        'abilities' => 'json',
        'last_used_at' => 'datetime',
        'expire_at' => 'datetime',
    ];
}
