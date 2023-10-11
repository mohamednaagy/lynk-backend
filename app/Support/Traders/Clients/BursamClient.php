<?php

namespace App\Support\Traders\Clients\BursamClient;

use App\Models\TraderOrder;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;

class BursamClient
{
    use Localizable;

    protected $middlewares = [];

    protected $fake;

    protected $traderOrderIdHeaderKey = 'X-TRADER-ORDER-ID';

    private function __construct(protected $traderOrder)
    {
        $this->fake = config('trader.providers.bursam.fake');

        if ($this->fake) {
            $this->registerFakeBursamResponses();
            $this->middlewares[] = Middleware::mapRequest(
                function ($request) use ($traderOrder) {
                    return $request->withHeader($this->traderOrderIdHeaderKey, $traderOrder->id);
                }
            );
        }
    }

    public static function of(TraderOrder $traderOrder)
    {
        return new static($traderOrder);
    }

    public function buyProduct($productCode)
    {
        $financingOrder = $this->traderOrder->order;

        return $this->http()
            ->post(
                'api/process/svc/bsas/order.json',
                [
                    'header' => [
                        'memberShortName' => config('trader.providers.bursam.member_short_name'),
                        'uuid' => $this->traderOrder->uuid_one,
                    ],
                    'request' => [
                        'serialNumber' => '1',
                        'bidOption' => 'Y',
                        'otcOption' => 'N',
                        'stbOption' => 'N',
                        'productCode' => $productCode,
                        'purchaseType' => 'P',
                        'clientName' => '',
                        'currency' => 'SAR',
                        'bidValue' => $financingOrder->amount->formatByDecimal(),
                        'valueDate' => now('Asia/Kuala_Lumpur')->format('Ymd'),
                        'tenor' => config('trader.providers.bursam.tenor'),
                        'otcCounterParty' => $financingOrder->customer_name,
                        'otcMurabaha' => '',
                        'otcMurabahaValue' => $financingOrder->selling_price->formatByDecimal(),
                        'eCertNo' => '',
                    ],
                ]
            );
    }

    public function sellProduct()
    {
        $financingOrder = $this->traderOrder->order;

        return $this->http()
            ->post(
                'api/process/svc/bsas/order.json',
                [
                    'header' => [
                        'memberShortName' => config('trader.providers.bursam.member_short_name'),
                        'uuid' => $this->traderOrder->uuid_two,
                    ],
                    'request' => [
                        'serialNumber' => '1',
                        'bidOption' => 'N',
                        'otcOption' => 'Y',
                        'stbOption' => 'Y',
                        'productCode' => $this->traderOrder->product_code,
                        'purchaseType' => 'P',
                        'clientName' => '',
                        'currency' => 'SAR',
                        'bidValue' => $financingOrder->amount->formatByDecimal(),
                        'valueDate' => now('Asia/Kuala_Lumpur')->format('Ymd'),
                        'tenor' => '00090',
                        'otcCounterParty' => $financingOrder->customer_name,
                        'otcMurabaha' => '',
                        'otcMurabahaValue' => $financingOrder->selling_price->formatByDecimal(),
                        'eCertNo' => $this->traderOrder->reference,
                    ],
                ]
            );
    }

    public function fetchBuyResult()
    {
        return $this->fetchOrderResult($this->traderOrder->uuid_one);
    }

    public function fetchSellResult()
    {
        return $this->fetchOrderResult($this->traderOrder->uuid_two);
    }

    private function fetchOrderResult($uuid)
    {
        return $this->http()
            ->post(
                'api/process/svc/bsas/orderResult.json',
                [
                    'header' => [
                        'memberShortName' => config('trader.providers.bursam.member_short_name'),
                        'uuid' => $uuid,
                    ],
                    'request' => [
                        'serialNumber' => '1',
                        'forceYN' => 'Y',
                        'maxWaitTime' => '10',
                        'waitAllDoneYN' => 'Y',
                    ],
                ]
            );
    }

    public function getBidXml()
    {
        return $this->http()
            ->post(
                'api/process/svc/bsas/bidXML.json',
                [
                    'input' => [
                        'membershortname' => config('trader.providers.bursam.member_short_name'),
                        'ecertno' => $this->traderOrder->reference,
                    ],
                ]
            );
    }

    public function getOtcXml()
    {
        return $this->http()
            ->post(
                'api/process/svc/bsas/otcXML.json',
                [
                    'input' => [
                        'membershortname' => config('trader.providers.bursam.member_short_name'),
                        'ecertno' => $this->traderOrder->reference,
                    ],
                ]
            );
    }

    public function getStbXml()
    {
        return $this->http()
            ->post(
                'api/process/svc/bsas/stbXML.json',
                [
                    'input' => [
                        'membershortname' => config('trader.providers.bursam.member_short_name'),
                        'ecertno' => $this->traderOrder->reference,
                    ],
                ]
            );
    }

    private function http(): PendingRequest
    {
        $instance = Http::bursam();

        foreach ($this->middlewares as $middleware) {
            $instance->withMiddleware($middleware);
        }

        return $instance;
    }

    private function registerFakeBursamResponses()
    {
        Http::fake([
            $this->buildUrl('/api/process/svc/bsas/order.json') => Http::response(),
            $this->buildUrl('/api/process/svc/bsas/orderResult.json') => function (Request $request) {
                $traderOrder = $this->getTraderOrderUsingFakeRequest($request);

                return Http::response([
                    'processingCount' => 0,
                    'body' => [
                        [
                            'bidErrNo' => '999',
                            'serialNumber' => 1,
                            'bidOption' => $traderOrder->uuid_two ? 'N' : 'Y',
                            'otcOption' => $traderOrder->uuid_two ? 'Y' : 'N',
                            'stbOption' => $traderOrder->uuid_two ? 'Y' : 'N',
                            'productCode' => $traderOrder->product_code,
                            'purchaseType' => 'P',
                            'clientName' => '',
                            'currency' => 'SAR',
                            'bidValue' => $traderOrder->order->amount->formatByDecimal(),
                            'valueDate' => now()->format('Ymd'),
                            'tenor' => '00074',
                            'otcCounterParty' => 'TIOMAN',
                            'otcMurabaha' => '',
                            'otcMurabahaValue' => '',
                            'ecertNo' => strtoupper(Str::random()),
                            'bidErrNo' => '999',
                            'bidMsg' => 'OK',
                            'otcErrNo' => '999',
                            'otcMsg' => 'OK',
                            'stbErrNo' => '999',
                            'stbMsg' => 'OK',
                            'regTime' => now()->format('YmdHis'),
                            'orderTime' => now()->format('YmdHis'),
                            'resultTime' => now()->format('YmdHis'),
                            'purchaseTime' => now()->format('YmdHis'),
                            'reportTime' => now()->format('YmdHis'),
                            'sellingTime' => now()->format('YmdHis'),
                            'unit' => 'Tonnage',
                            'price' => $traderOrder->order->amount->formatByDecimal(),
                        ],
                    ],
                ]);
            },
            $this->buildUrl('/api/process/svc/bsas/bidXML.json') => function (Request $request) {
                $traderOrder = $this->getTraderOrderUsingFakeRequest($request);

                return Http::response([
                    'ECERTNO' => $traderOrder->reference,
                    'BUYER' => 'LYNK LLC',
                    'OWNER' => 'RHB',
                    'BIDNO' => '4',
                    'TOTALVALUE' => $traderOrder->order->amount->formatByDecimal(),
                    'CURRENCY' => 'SAR',
                    'PRICE' => $traderOrder->order->amount->formatByDecimal(),
                    'PRICE_MYR_EQUIVALENT' => $traderOrder->order->amount->multiply(1.26)->formatByDecimal(),
                    'PURCHASETIMEDATE' => $traderOrder->created_at->format('H:i:s.v d M Y'),
                    'VALUEDATE' => $traderOrder->created_at->format('d M Y'),
                    'PNAME' => $traderOrder->product_code,
                    'PVOLUME' => $volume = rand(1, 20),
                    'LINE' => [
                        [
                            'SUPPLIER' => 'SPTT301',
                            'VOLUME' => $volume,
                        ],
                    ],
                ]);
            },
            $this->buildUrl('/api/process/svc/bsas/otcXML.json') => function (Request $request) {
                $traderOrder = $this->getTraderOrderUsingFakeRequest($request);

                return Http::response([
                    'ECERTNO' => $traderOrder->reference,
                    'SELLER' => 'LYNK LLC',
                    'BUYER' => 'BSAS',
                    'TOTALVALUE' => $traderOrder->order->amount->formatByDecimal(),
                    'CURRENCY' => 'SAR',
                    'PRICE' => $traderOrder->order->amount->formatByDecimal(),
                    'PRICE_MYR_EQUIVALENT' => $traderOrder->order->amount->multiply(1.26)->formatByDecimal(),
                    'MURABAHAVALUE' => $traderOrder->order->amount->formatByDecimal(),
                    'REPORTINGTIMEDATE' => $traderOrder->created_at->format('H:i:s.v d M Y'),
                    'VALUEDATE' => $traderOrder->created_at->format('d M Y'),
                    'PNAME' => $traderOrder->product_code,
                    'PVOLUME' => $traderOrder->products[0]['quantity'],
                    'LINE' => [
                        [
                            'SUPPLIER' => 'RAH54',
                            'VOLUME' => $traderOrder->products[0]['quantity'],
                        ],
                    ],
                ]);
            },
            $this->buildUrl('api/process/svc/bsas/stbXML.json') => function (Request $request) {
                $traderOrder = $this->getTraderOrderUsingFakeRequest($request);

                return Http::response([
                    'ECERTNO' => $traderOrder->reference,
                    'SELLER' => 'LYNK LLC',
                    'BUYER' => $traderOrder->order->customer_name,
                    'TOTALVALUE' => $traderOrder->order->amount->formatByDecimal(),
                    'CURRENCY' => 'SAR',
                    'PRICE' => $traderOrder->order->amount->formatByDecimal(),
                    'PRICE_MYR_EQUIVALENT' => $traderOrder->order->amount->multiply(1.26)->formatByDecimal(),
                    'MURABAHAVALUE' => $traderOrder->order->amount->formatByDecimal(),
                    'REPORTINGTIMEDATE' => $traderOrder->created_at->format('H:i:s.v d M Y'),
                    'VALUEDATE' => $traderOrder->created_at->format('d M Y'),
                    'PNAME' => $traderOrder->product_code,
                    'PVOLUME' => $traderOrder->products[0]['quantity'],
                    'LINE' => [
                        [
                            'SUPPLIER' => 'RAH54',
                            'VOLUME' => $traderOrder->products[0]['quantity'],
                        ],
                    ],
                ]);
            },
        ]);
    }

    private function getTraderOrderUsingFakeRequest(Request $request)
    {
        return TraderOrder::find($request->header($this->traderOrderIdHeaderKey)[0]);
    }

    private function buildUrl($path)
    {
        return rtrim(config('trader.providers.bursam.base_url'), '/').'/'.ltrim($path, '/');
    }
}
