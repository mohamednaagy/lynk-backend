<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class NotificationSettingTransformer extends TransformerAbstract
{
    public function transform($data): array
    {
        if (is_array($data)) {
            return [
                'id' => $data['id'],
                'name' => $data['name'],
                'is_enabled' => (bool) $data['is_enabled'],
            ];
        }

        return [
            'id' => $data->id,
            'name' => $data->name,
            'is_enabled' => (bool) $data->is_enabled,
        ];
    }
}
