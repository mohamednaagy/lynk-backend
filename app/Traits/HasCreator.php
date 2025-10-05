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

    private $maskedCreator = [
        'id' => null,
        'name' => 'user',
    ];

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, $this->key);
    }

    public function getCreator(): array
    {
        $user = auth()->user();
        if (! $user || ! $user->isAdmin()) {
            return $this->maskedCreator;
        }

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
