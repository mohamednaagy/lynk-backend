<?php

namespace App\Actions\Notifications;

use App\Actions\Contracts\Notifications\BuildUserNotificationsQuery;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BuildUserNotificationsQueryAction implements BuildUserNotificationsQuery
{
    protected User $user;

    protected bool $unreadOnly = false;

    public function handle(): Builder
    {
        $relation = $this->user->notifications();

        // Apply filters to the relation
        if ($this->unreadOnly) {
            $relation->whereNull('read_at');
        }

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

    public function setUnreadOnly(bool $unreadOnly): self
    {
        $this->unreadOnly = $unreadOnly;

        return $this;
    }
}
