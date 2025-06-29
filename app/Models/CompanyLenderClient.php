<?php

namespace App\Models;

use App\Enums\CompanyLenderClientType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyLenderClient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'company_id',
        'type',
        'national_id',
        'auto_complete_sell',
    ];

    protected $casts = [
        'type' => CompanyLenderClientType::class,
        'auto_complete_sell' => 'boolean',
    ];

    public function autoSellPeriods(): HasMany
    {
        return $this->hasMany(ClientAutoSellPeriod::class, 'company_lender_client_id');
    }

    public function lender(): BelongsTo
    {
        return $this->belongsTo(Lender::class, 'company_id');
    }
}
