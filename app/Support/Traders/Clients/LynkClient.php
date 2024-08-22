<?php

namespace App\Support\Traders\Clients;

use App\Actions\LocalMarket\PurchaseProductAction;
use App\Models\TraderOrder;
use App\Settings\Classes\LocalMurabahaSettings;
use Illuminate\Support\Traits\Localizable;

class LynkClient
{
    use Localizable;

    protected $middlewares = [];

    protected $fake;

    protected $traderOrderIdHeaderKey = 'X-TRADER-ORDER-ID';

    private function __construct(protected $traderOrder) {}

    private function isTraderOrderInitiatedByFake()
    {
        return strpos($this->traderOrder->reference, '-') === false;
    }

    public static function of(TraderOrder $traderOrder)
    {
        return new static($traderOrder);
    }

    public function buyProduct()
    {
        // calculate and lock the units if we can handle the loan
        // buy the units to the company

        $financingOrder = $this->traderOrder->order;
        $number_of_rotations = app(LocalMurabahaSettings::class)->default_trade_order_rotation_count ?? 0;

        return app(PurchaseProductAction::class)->handle($financingOrder, $financingOrder->company_id, $financingOrder->company->preferred_market_type, $financingOrder->amount->convertAndFormatByDecimal(), $number_of_rotations);
    }

    public function sellProduct()
    {
        //TODO to be handled
    }
}
