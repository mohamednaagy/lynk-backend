<?php

namespace App\Support\Traders\Clients;

use App\Actions\Contracts\LocalMarket\CancelOrder;
use App\Actions\Contracts\LocalMarket\ConfirmDeliverProducts;
use App\Actions\Contracts\LocalMarket\CreateLocalMarketOrder;
use App\Actions\Contracts\LocalMarket\RequestDeliverProducts;
use App\Actions\Contracts\LocalMarket\SellCommodities;
use App\Actions\Contracts\LocalMarket\TransferOwnerShip;
use App\Models\TraderOrder;
use Illuminate\Support\Facades\Log;
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

    public function createOrder(array $commodityTypesId = [], $forceCommodityType = false)
    {
        try {
            $financingOrder = $this->traderOrder->order;

            $data = $this->prepareOrderData($financingOrder);
            $data['force_commodity_type'] = $forceCommodityType;
            $data['preferred_commodity_type'] = $commodityTypesId;
            Log::channel('local_market')->info("Data prepared for Trader Order ID: {$this->traderOrder->id}", $data);

            return app(CreateLocalMarketOrder::class)->handle($data);
        } catch (\Exception $e) {
            Log::channel('local_market')->error("Error creating LocalMarketOrder for Trader Order ID: {$this->traderOrder->id}", [
                'exception' => $e->getMessage(),
            ]);
        }

    }

    public function sellProduct()
    {
        return app(SellCommodities::class)->handle($this->traderOrder->reference);
    }

    public function transferOwnershipToCustomer()
    {
        return app(TransferOwnerShip::class)->handle($this->traderOrder->reference);
    }

    public function cancelOrder()
    {
        return app(CancelOrder::class)->handle($this->traderOrder->reference);
    }

    public function confirmDeliverProducts()
    {
        return app(ConfirmDeliverProducts::class)->handle($this->traderOrder->reference);
    }

    public function requestDeliverProducts()
    {
        return app(RequestDeliverProducts::class)->handle($this->traderOrder->reference);
    }

    /**
     * Prepare data for the local market order creation.
     *
     * @param  \App\Models\FinancingOrder  $financingOrder
     */
    private function prepareOrderData($financingOrder): array
    {
        return [
            'currency' => $financingOrder->currency,
            'national_id' => $financingOrder->national_id,
            'amount' => $financingOrder->amount->convertAndFormatByDecimal(),
            'customer_name' => $financingOrder->customer_name,
            'external_order_no' => $this->traderOrder->reference,
            'source' => $this->traderOrder->provider,
            'company_id' => $financingOrder->company_id,
            'buying_uuid' => $this->traderOrder->uuid_one,
        ];
    }
}
