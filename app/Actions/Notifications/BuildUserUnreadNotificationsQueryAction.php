<?php

namespace App\Actions\Notifications;

use App\Actions\Contracts\Notifications\BuildUserUnreadNotificationsQuery;
use App\Models\User;
use App\Support\QueryScoper\Scopes\Notifications\RecentNotificationsScope;
use App\Support\QueryScoper\Scopes\Notifications\UnreadNotificationsScope;

class BuildUserUnreadNotificationsQueryAction implements BuildUserUnreadNotificationsQuery
{
    protected ?User $user = null;

    public function handle(): int
    {
        if (! $this->user) {
            return 0;
        }

        return $this->user->notifications()
            ->getQuery()
            ->toScopes($this->scopes())
            ->count();
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
            'unread' => UnreadNotificationsScope::class,
        ];
    }
}
