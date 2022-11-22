<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'holder_type',
        'holder_id',
        'name',
    ];

    public function holder(): MorphTo
    {
        return $this->morphTo();
    }
}
