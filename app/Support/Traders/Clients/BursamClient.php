<?php

namespace App\Support\Traders\Clients\BursamClient;

use App\Exceptions\RateLimitExceededException;
use App\Models\TraderOrder;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
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
        if (empty($traderOrder->reference)) {
            $this->fake = config('trader.providers.bursam.fake');
        } else {
            $this->fake = $this->isTraderOrderInitiatedByFake();
        }

        if ($this->fake) {
            $this->registerFakeBursamResponses();
            $this->middlewares[] = Middleware::mapRequest(
                function ($request) use ($traderOrder) {
                    return $request->withHeader($this->traderOrderIdHeaderKey, $traderOrder->id);
                }
            );
        }
    }

    private function isTraderOrderInitiatedByFake()
    {
        return strpos($this->traderOrder->reference, '-') === false;
    }

    public static function of(TraderOrder $traderOrder)
    {
        return new static($traderOrder);
    }

    public function buyProduct($productCode)
    {
        $financingOrder = $this->traderOrder->order;

        return $this->rateLimitRequest(fn () => $this->http()
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
                        'bidValue' => (float) $financingOrder->amount->convertAndFormatByDecimal(),
                        'valueDate' => now('Asia/Kuala_Lumpur')->format('Ymd'),
                        'tenor' => config('trader.providers.bursam.tenor'),
                        'otcCounterParty' => $financingOrder->customer_name,
                        'otcMurabaha' => '',
                        'otcMurabahaValue' => (float) $financingOrder->selling_price->convertAndFormatByDecimal(),
                        'eCertNo' => '',
                    ],
                ]
            ));
    }

    public function sellProduct()
    {
        $financingOrder = $this->traderOrder->order;

        return $this->rateLimitRequest(
            fn () => $this->http()
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
                            'bidValue' => (float) $financingOrder->amount->convertAndFormatByDecimal(),
                            'valueDate' => now('Asia/Kuala_Lumpur')->format('Ymd'),
                            'tenor' => '00090',
                            'otcCounterParty' => $financingOrder->customer_name,
                            'otcMurabaha' => '',
                            'otcMurabahaValue' => (float) $financingOrder->selling_price->convertAndFormatByDecimal(),
                            'eCertNo' => $this->traderOrder->reference,
                        ],
                    ]
                )
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
        return $this->rateLimitRequest(
            fn () => $this->http()
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
                )
        );
    }

    public function getBidXml()
    {
        return $this->rateLimitRequest(
            fn () => $this->http()
                ->post(
                    'api/process/svc/bsas/bidXML.json',
                    [
                        'input' => [
                            'membershortname' => config('trader.providers.bursam.member_short_name'),
                            'ecertno' => $this->traderOrder->reference,
                        ],
                    ]
                )
        );
    }

    public function getOtcXml()
    {
        return $this->rateLimitRequest(
            fn () => $this->http()
                ->post(
                    'api/process/svc/bsas/otcXML.json',
                    [
                        'input' => [
                            'membershortname' => config('trader.providers.bursam.member_short_name'),
                            'ecertno' => $this->traderOrder->reference,
                        ],
                    ]
                )
        );
    }

    public function getStbXml()
    {
        return $this->rateLimitRequest(
            fn () => $this->http()
                ->post(
                    'api/process/svc/bsas/stbXML.json',
                    [
                        'input' => [
                            'membershortname' => config('trader.providers.bursam.member_short_name'),
                            'ecertno' => $this->traderOrder->reference,
                        ],
                    ]
                )
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

    protected function rateLimitRequest($callback, $remainingRetries = 0)
    {
        if ($remainingRetries > (int) config('trader.providers.bursam.rate_limit.max_retries_before_exception')) {
            $exception = new RateLimitExceededException('bursam_api');

            $exception->setContext([
                'trader_order_id' => $this->traderOrder->id,
                'provider' => $this->traderOrder->provider,
                'version' => $this->traderOrder->version,
            ]);

            throw $exception;
        }

        if ($remainingRetries > 0) {
            sleep(((int) config('trader.providers.bursam.rate_limit.decay_seconds')) + 1);
        }

        $executed = RateLimiter::attempt(
            'bursam_api',
            config('trader.providers.bursam.rate_limit.max_attempts'),
            $callback,
            config('trader.providers.bursam.rate_limit.decay_seconds'),
        );

        if ($executed === false) {
            return $this->rateLimitRequest($callback, ++$remainingRetries);
        }

        return $executed;
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
                            'bidValue' => (float) $traderOrder->order->amount->convertAndFormatByDecimal(),
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
                            'price' => (float) $traderOrder->order->amount->convertAndFormatByDecimal(),
                        ],
                    ],
                ]);
            },
            $this->buildUrl('/api/process/svc/bsas/bidXML.json') => function (Request $request) {
                $traderOrder = $this->getTraderOrderUsingFakeRequest($request);

                return Http::response([
                    'ECERTNO' => $traderOrder->reference,
                    'BUYER' => 'LYNK LLC',
                    'OWNER' => 'LYNK LLC',
                    'BIDNO' => '4',
                    'TOTALVALUE' => (float) $traderOrder->order->amount->convertAndFormatByDecimal(),
                    'CURRENCY' => 'SAR',
                    'PRICE' => (float) $traderOrder->order->amount->convertAndFormatByDecimal(),
                    'PRICE_MYR_EQUIVALENT' => (float) $traderOrder->order->amount->multiply(1.26)->convertAndFormatByDecimal(),
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
                    'TOTALVALUE' => (float) $traderOrder->order->amount->convertAndFormatByDecimal(),
                    'CURRENCY' => 'SAR',
                    'PRICE' => (float) $traderOrder->order->amount->convertAndFormatByDecimal(),
                    'PRICE_MYR_EQUIVALENT' => (float) $traderOrder->order->amount->multiply(1.26)->convertAndFormatByDecimal(),
                    'MURABAHAVALUE' => (float) $traderOrder->order->amount->convertAndFormatByDecimal(),
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
                    'SELLER' => $traderOrder->order->customer_name,
                    'BUYER' => 'BURSA MALAYSIA ISLAMIC SERVICES',
                    'TOTALVALUE' => (float) $traderOrder->order->amount->convertAndFormatByDecimal(),
                    'CURRENCY' => 'SAR',
                    'PRICE' => (float) $traderOrder->order->amount->convertAndFormatByDecimal(),
                    'PRICE_MYR_EQUIVALENT' => (float) $traderOrder->order->amount->multiply(1.26)->convertAndFormatByDecimal(),
                    'MURABAHAVALUE' => (float) $traderOrder->order->amount->convertAndFormatByDecimal(),
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
