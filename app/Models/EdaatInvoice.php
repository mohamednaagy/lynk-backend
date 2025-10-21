<?php

namespace App\Models;

use App\Enums\EdaatInvoiceStatus;
use App\Support\Money\Casts\MoneyStringCast;
use App\Support\QueryScoper\HasScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class EdaatInvoice extends Model
{
    use BelongsToTenant, HasFactory, HasScopes;

    protected $fillable = [
        'amount',
        'invoice_number',
        'status',
        'creator_id',
        'company_id',
        'paid_at',
    ];

    protected $casts = [
        'status' => EdaatInvoiceStatus::class,
        'amount' => MoneyStringCast::class.':currency',
        'paid_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
