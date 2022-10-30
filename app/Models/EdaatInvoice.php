<?php

namespace App\Models;

use App\Enums\EdaatInvoiceStatus;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class EdaatInvoice extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'amount',
        'invoice_number',
        'status',
    ];

    protected $casts = [
        'status' => EdaatInvoiceStatus::class,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
