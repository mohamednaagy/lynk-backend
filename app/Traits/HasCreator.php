<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\TenantScope;

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, $this->key)->withoutGlobalScope(TenantScope::class);
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
