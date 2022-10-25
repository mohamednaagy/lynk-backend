<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class EdaatInvoice extends Model
{
    use HasFactory, BelongsToTenant;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
