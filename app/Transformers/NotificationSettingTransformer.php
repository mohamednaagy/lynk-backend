<?php

namespace App\Transformers;

use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class NotificationSettingTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'name',
        'label',
        'channels',
    ];

    protected array $defaultIncludes = [
        'id',
        'name',
        'label',
        'channels',
    ];

    public function transform($data): array
    {
        return [];
    }

    public function includeId($data): Primitive
    {
        return $this->primitive(data_get($data, 'id'));
    }

    public function includeName($data): Primitive
    {
        return $this->primitive(data_get($data, 'name'));
    }

    public function includeLabel($data): Primitive
    {
        return $this->primitive(data_get($data, 'label'));
    }

    public function includeChannels($data): Primitive
    {
        return $this->primitive(data_get($data, 'channels'));
    }
}
