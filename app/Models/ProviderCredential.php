<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderCredential extends Model
{
    use HasFactory;

    protected $table = 'provider_credential';

    protected $fillable = [
        'provider_name',
        'access_token',
    ];
}
