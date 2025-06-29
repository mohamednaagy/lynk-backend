<?php

namespace App\Models;

use App\Enums\MediaCollections\ClientAutoSellPeriodMediaCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ClientAutoSellPeriod extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'company_lender_client_id',
        'effective_start',
        'effective_end',
    ];

    protected $casts = [
        'effective_start' => 'date',
        'effective_end' => 'date',
    ];

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection(ClientAutoSellPeriodMediaCollection::SupportingDocument)
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(CompanyLenderClient::class, 'company_lender_client_id');
    }
}
