<?php

namespace App\Traits;

use App\Models\User;

trait HasCreator
{
    private $key = 'creator_id';

    private $defaultCreator = [
        'id' => null,
        'name' => 'LYNK System',
    ];

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, $this->key);
    }

    public function getCreator(): array
    {
        $creator = $this->creator;
        if ($creator) {
            return [
                'id' => $creator->id,
                'name' => $creator->fullName,
            ];
        }

        return $this->defaultCreator;
    }
}
