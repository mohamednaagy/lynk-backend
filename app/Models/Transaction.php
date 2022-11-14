<?php

namespace App\Models;

use Bavix\Wallet\Models\Transaction as BaseTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Transaction extends BaseTransaction implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
}
