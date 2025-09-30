<?php

namespace App\Traits;

use App\Models\User;

trait HasCreator
{
    private $key = 'creator_id';

    public function creator()
    {
        return $this->belongsTo(User::class, $this->key);
    }
}
