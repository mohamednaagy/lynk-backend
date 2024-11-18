<?php

namespace App\Support\Traders\Clients;

use App\Actions\Contracts\LocalMarket\CancelOrder;
use App\Actions\Contracts\LocalMarket\CreateLocalMarketOrder;
use App\Actions\Contracts\LocalMarket\SellCommodities;
use App\Actions\Contracts\LocalMarket\TransferOwnerShip;
use App\Models\LocalMarketOrder;
use App\Models\TraderOrder;
use App\Settings\Classes\LocalMurabahaSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Localizable;

class LynkClient
{
    use Localizable;

    protected $middlewares = [];

    protected $fake;

    protected $LocalMarketOrder;

    protected $traderOrderIdHeaderKey = 'X-TRADER-ORDER-ID';

    private function __construct(protected $traderOrder, LocalMarketOrder $localMarketOrder) {}

    private function isTraderOrderInitiatedByFake()
    {
        return strpos($this->traderOrder->reference, '-') === false;
    }

    public static function of(TraderOrder $traderOrder, LocalMarketOrder $localMarketOrder)
    {
        return new static($traderOrder, $localMarketOrder);
    }

    public function createOrder()
    {
        try {
            $financingOrder = $this->traderOrder->order;

            $data = $this->prepareOrderData($financingOrder);

            Log::channel('local_market')->info("Data prepared for Trader Order ID: {$this->traderOrder->id}", $data);

            return $this->LocalMarketOrder->create($data);
        } catch (\Exception $e) {
            Log::channel('local_market')->error("Error creating LocalMarketOrder for Trader Order ID: {$this->traderOrder->id}", [
                'exception' => $e->getMessage()
            ]);
        }

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
        $trader = $this->traderOrder;
        $localMarketOrder = LocalMarketOrder::where('external_order_no', $trader->reference)->first();

        return app(SellCommodities::class)->handle($localMarketOrder);
    }

    public function transferOwnershipToCustomer()
    {
        $trader = $this->traderOrder;
        $localMarketOrder = LocalMarketOrder::where('external_order_no', $trader->reference)->first();

        return app(TransferOwnerShip::class)->handle($localMarketOrder);
    }

    public function cancelOrder()
    {
        $trader = $this->traderOrder;
        $localMarketOrder = LocalMarketOrder::where('external_order_no', $trader->reference)->first();

        return app(CancelOrder::class)->handle($localMarketOrder);
    }

    /**
     * Prepare data for the local market order creation.
     *
     * @param \App\Models\FinancingOrder $financingOrder
     * @return array
     */
    private function prepareOrderData($financingOrder): array
    {
        return [
            'currency'               => $financingOrder->currency,
            'national_id'            => $financingOrder->national_id,
            'amount'                 => $financingOrder->amount->convertAndFormatByDecimal(),
            'customer_name'          => $financingOrder->customer_name,
            'external_order_no'      => $this->traderOrder->reference,
            'source'                 => $this->traderOrder->provider,
            'company_id'             => $financingOrder->company_id,
            'buying_uuid'            => $this->traderOrder->uuid_one,
            'preferred_commodity_type'=> $this->getPreferredCommodityTypes($financingOrder->company),
        ];
    }

    /**
     * Get preferred commodity types for a company.
     *
     * @param \App\Models\Company $company
     * @return array
     */
    private function getPreferredCommodityTypes($company): array
    {
        return $company->commodityTypes()->pluck('commodity_type_id')->toArray() ?? [];
    }
}
