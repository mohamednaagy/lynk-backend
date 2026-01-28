<?php

namespace App\Actions\Notifications;

use App\Actions\Contracts\Notifications\BuildUserNotificationsQuery;
use App\Models\User;
use App\Support\QueryScoper\Scopes\Notifications\RecentNotificationsScope;
use Illuminate\Database\Eloquent\Builder;

class BuildUserNotificationsQueryAction implements BuildUserNotificationsQuery
{
    protected ?User $user = null;

    public function handle(): ?Builder
    {
        if (! $this->user) {
            return null;
        }

        return $this->user->notifications()
            ->getQuery()
            ->toScopes($this->scopes())
            ->latest();
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    private function scopes(): array
    {
        return [
            'recent' => RecentNotificationsScope::class,
        ];
    }
}
