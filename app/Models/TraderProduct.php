<?php

namespace App\Models;

use App\Enums\TraderProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TraderProduct extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
        'code',
        'provider',
        'order',
        'status',
    ];

    protected $casts = [
        'status' => TraderProductStatus::class,
    ];
}
