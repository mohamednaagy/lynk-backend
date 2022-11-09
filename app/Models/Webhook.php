<?php

namespace App\Models;

use App\Enums\WebhookType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
