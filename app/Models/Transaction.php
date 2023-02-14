<?php

namespace App\Models;

use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Support\Money\Casts\MoneyStringCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Transaction extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasUuids;

    public function uniqueIds()
    {
        return ['uuid'];
    }

    protected $fillable = [
        'id',
        'wallet_id',
        'reference_number',
        'amount',
        'meta',
        'reason',
    ];

    protected $casts = [
        'meta' => 'array',
        'amount' => MoneyStringCast::class.':currency',
    ];

    public function getConnectionName()
    {
        return Config::get('wallet.database.connection', parent::getConnectionName());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(TransactionMediaCollection::Attachments)
            ->singleFile();
    }
}
