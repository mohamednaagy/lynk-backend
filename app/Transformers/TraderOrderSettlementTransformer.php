<?php

namespace App\Transformers;

use App\Models\TraderOrderSettlement;
use League\Fractal\Resource\NullResource;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class TraderOrderSettlementTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    protected array $availableIncludes = [
        'is_commodities_settled',
        'created_at',
        'message',
        'creator',
    ];

    public function transform(TraderOrderSettlement $settlement): array
    {
        return [];
    }

    public function includeIsCommoditiesSettled(TraderOrderSettlement $settlement): Primitive
    {
        return $this->primitive($settlement->is_commodities_settled);
    }

    public function includeCreatedAt(TraderOrderSettlement $settlement): Primitive
    {
        return $this->primitive($settlement->created_at);
    }

    public function includeMessage(TraderOrderSettlement $settlement): Primitive
    {
        // Settled - no message needed
        if ($settlement->is_commodities_settled === true) {
            return $this->primitive('');
        }

        // Pending check - show in-progress message
        if ($settlement->isSettlementInProgress()) {
            return $this->primitive(__('error.settlement_check_in_progress'));
        }

        return $this->primitive(
            __('error.unable_to_generate_sell_confirmation_certificate', [
                'created_at' => $settlement->created_at,
                'creator_name' => $settlement->creator->full_name,
            ])
        );
    }

    public function includeCreator(TraderOrderSettlement $settlement): Primitive|NullResource
    {
        if (! $settlement->creator) {
            return $this->null();
        }

        return $this->primitive($settlement->getCreator());
    }
}
