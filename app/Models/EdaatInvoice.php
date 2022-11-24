<?php

namespace App\Models;

use App\Enums\EdaatInvoiceStatus;
use App\Support\QueryScoper\HasScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class EdaatInvoice extends Model
{
    use HasFactory, BelongsToTenant, HasScopes;

    protected $fillable = [
        'amount',
        'invoice_number',
        'status',
        'creator_id',
        'company_id',
    ];

    protected $casts = [
        'status' => EdaatInvoiceStatus::class,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
