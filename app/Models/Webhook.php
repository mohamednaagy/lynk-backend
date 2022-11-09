<?php

namespace App\Models;

use App\Enums\WebhookType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Webhook extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $casts = [
        'type' => WebhookType::class,
    ];

    protected $fillable = [
        'url',
        'type',
    ];
}
