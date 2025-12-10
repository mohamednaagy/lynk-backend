<?php

namespace App\Transformers;

use App\Enums\SystemNotificationType;
use League\Fractal\TransformerAbstract;

class NotificationSettingTransformer extends TransformerAbstract
{
    public function transform($data): array
    {
        if (is_array($data)) {
            $label = SystemNotificationType::getDescription($data['name']);

            return [
                'id' => $data['id'],
                'name' => $data['name'],
                'label' => $label,
                'is_enabled' => (bool) $data['is_enabled'],
            ];
        }

        $label = SystemNotificationType::getDescription($data->name);

        return [
            'id' => $data->id,
            'name' => $data->name,
            'label' => $label,
            'is_enabled' => (bool) $data->is_enabled,
        ];
    }
}
