<?php

namespace App\Traits;

use App\Models\User;

trait HasCreator
{
    private $key = 'creator_id';

    private $defaultCreator = [
        'id' => null,
        'name' => 'Lynk System',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, $this->key);
    }

    public function getCreator()
    {
        if ($this->creator) {
            return [
                'id' => $this->creator->id,
                'name' => $this->creator->fullName,
            ];
        }

        return $this->defaultCreator;
    }
}
