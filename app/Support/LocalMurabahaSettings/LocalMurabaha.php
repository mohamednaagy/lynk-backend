<?php

namespace App\Support\LocalMurabahaSettings;

class LocalMurabaha
{
    public function __construct(
        private int $default_trade_order_roatation_count,
    ) {
    }

    public static function fromArray(array $data): LocalMurabaha
    {
        return new static(
            $data['default_trade_order_roatation_count'],
        );
    }

    public function getDefaultTradeOrderRoatationCount(): int
    {
        return $this->default_trade_order_roatation_count;
    }
}
