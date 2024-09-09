<?php

namespace App\Support\LocalMurabahaSettings;

class LocalMurabaha
{
    public function __construct(
        private int $default_trade_order_rotation_count,
        private int $default_contract_sign_time_limit,
    ) {}

    public static function fromArray(array $data): LocalMurabaha
    {
        return new static(
            $data['default_trade_order_rotation_count'],
            $data['default_contract_sign_time_limit']
        );
    }

    public function getDefaultTradeOrderRoatationCount(): int
    {
        return $this->default_trade_order_rotation_count;

    }

    public function getDefaultContractSignTimeLimit(): int
    {
        return $this->default_contract_sign_time_limit;

    }
}
