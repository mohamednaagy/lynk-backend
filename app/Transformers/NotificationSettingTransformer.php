<?php

namespace App\Transformers;

use App\Enums\SystemNotificationType;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class NotificationSettingTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'id',
        'name',
        'label',
        'is_enabled',
    ];

    protected array $defaultIncludes = [
        'id',
        'name',
        'label',
        'is_enabled',
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
        $name = data_get($data, 'name');

        return $this->primitive(SystemNotificationType::getDescription($name));
    }

    public function includeIsEnabled($data): Primitive
    {
        return $this->primitive((bool) data_get($data, 'is_enabled'));
    }
}
