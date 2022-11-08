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
        'webhook_type' => WebhookType::class,
    ];

    protected $fillable = [
        'webhook_url',
        'webhook_secret_key',
        'webhook_type',
    ];
}
