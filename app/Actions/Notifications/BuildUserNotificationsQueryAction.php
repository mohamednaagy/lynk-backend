<?php

namespace App\Actions\Notifications;

use App\Actions\Contracts\Notifications\BuildUserNotificationsQuery;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BuildUserNotificationsQueryAction implements BuildUserNotificationsQuery
{
    protected ?User $user = null;

    public function handle(): ?Builder
    {
        if (! $this->user) {
            return null;
        }

        $relation = $this->user->notifications();

        $relation->where('created_at', '>=', now()->subDays(config('notifications.panel.days')));

        // Get the underlying query builder and apply ordering
        $builder = $relation->getQuery();
        $builder->latest();

        return $builder;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
