<?php

namespace App\Transformers;

use Illuminate\Notifications\DatabaseNotification;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class NotificationTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [
        'id',
        'type',
        'data',
        'read_at',
        'created_at',
        'updated_at',
    ];

    protected array $availableIncludes = [
        'id',
        'type',
        'data',
        'read_at',
        'created_at',
        'updated_at',
    ];

    public function transform(DatabaseNotification $notification): array
    {
        return [];
    }

    public function includeId(DatabaseNotification $notification): Primitive
    {
        return $this->primitive($notification->id);
    }

    public function includeType(DatabaseNotification $notification): Primitive
    {
        return $this->primitive($notification->type);
    }

    public function includeData(DatabaseNotification $notification): Primitive
    {
        return $this->primitive($notification->data);
    }

    public function includeReadAt(DatabaseNotification $notification): Primitive
    {
        return $this->primitive(
            $notification->read_at ? $notification->read_at->toISOString() : null
        );
    }

    public function includeCreatedAt(DatabaseNotification $notification): Primitive
    {
        return $this->primitive($notification->created_at->toISOString());
    }

    public function includeUpdatedAt(DatabaseNotification $notification): Primitive
    {
        return $this->primitive($notification->updated_at->toISOString());
    }
}
