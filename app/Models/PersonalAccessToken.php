<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expire_at',
    ];

    protected $casts = [
        'abilities' => 'json',
        'last_used_at' => 'datetime',
        'expire_at' => 'datetime',
    ];
}
