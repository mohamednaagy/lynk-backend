<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class NotificationSettingTransformer extends TransformerAbstract
{
    public function transform(array $data): array
    {
        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'is_enabled' => $data['is_enabled'],
        ];
    }
}
