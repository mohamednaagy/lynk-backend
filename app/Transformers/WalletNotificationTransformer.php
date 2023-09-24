<?php

namespace App\Transformers;

use App\Enums\WalletNotificationType;
use App\Models\WalletNotification;
use League\Fractal\TransformerAbstract;

class WalletNotificationTransformer extends TransformerAbstract
{
    public function transform(WalletNotification $walletNotification): array
    {
        return [
            'id' => $walletNotification->id,
            'type' => [
                'value' => $walletNotification->type->value,
                'label' => $walletNotification->type->description,
            ],
            'value' => $walletNotification->type->is(WalletNotificationType::ORDER_COUNT)
                ? intval($walletNotification->value->formatByDecimal())
                : $walletNotification->value->formatByDecimal(),
        ];
    }
}
