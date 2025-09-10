<?php

namespace App\Support\Traders\Clients;

use App\Enums\BursamErrorCode;
use App\Exceptions\RateLimitExceededException;
use App\Models\TraderOrder;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        // Constructor logging
        log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('Initializing BursamClient', $traderOrder), [
            'financingOrderId' => $traderOrder->financing_order_id,
            'traderOrderId' => $traderOrder->id,
            'fake' => $this->fake,
        ]);
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

        log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('bursa purchasing step => buy product', $this->traderOrder), [
            'financingOrderId' => $financingOrder->id,
            'traderOrderId' => $this->traderOrder->id,
            'time' => now()]);
        $url = 'api/process/svc/bsas/order.json';

        $request = [
            [
                'serialNumber' => '1',
                'bidOption' => 'Y',
                'otcOption' => 'N',
                'stbOption' => 'N',
                'productCode' => $productCode,
                'purchaseType' => 'P',
                'clientName' => '',
                'currency' => 'SAR',
                'bidValue' => (string) $financingOrder->amount->convertAndFormatByDecimal(),
                'valueDate' => now('Asia/Kuala_Lumpur')->format('Ymd'),
                'tenor' => config('trader.providers.bursam.tenor'),
                'otcCounterParty' => $financingOrder->customer_name,
                'otcMurabaha' => '',
                'otcMurabahaValue' => (string) $financingOrder->selling_price->convertAndFormatByDecimal(),
                'eCertNo' => '',
            ],
        ];

        $requestHeader = [
            'memberShortName' => config('trader.providers.bursam.member_short_name'),
            'uuid' => $this->traderOrder->uuid_one,
        ];

        $response = $this->rateLimitRequest(fn () => $this->http()
            ->post(
                $url,
                [
                    'header' => $requestHeader,
                    'request' => $request,
                ]
            ));

        log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('Malaysia Bursa buyProduct request', $this->traderOrder), [
            'financingOrderId' => $financingOrder->id,
            'traderOrderId' => $this->traderOrder->id,
            'url' => $url,
            'request' => $request,
            'headers' => $requestHeader,
            'response' => $response->json(),
            'statusCode' => $response->getStatusCode(),
        ]);

        return $response;
    }

    public function isValidResponse($response, string $context = 'general'): bool
    {
        if (empty($response)) {
            $this->logError("Response is empty when ($context)", $response);

            return false;
        }

        if (! empty($response->json('header.errorCode'))) {
            $this->logError("Header errorCode is not empty when ($context)", $response);

            return false;
        }

        if ($response->json('body.0.statusCode') != 0) {
            $this->logError("Body statusCode is not zero when ($context)", $response);

            return false;
        }

        if ($response->json('SUCCESSYN') == 'N') {
            $this->logError("SUCCESSYN is equal N when ($context)", $response);

            return false;
        }

        return true;

    }

    public function validateFetchYNN($response): bool
    {
        if ($this->isValidResponse($response, 'fetch_ynn')) {
            $bidErrNo = $response->json('body.0.bidErrNo');
            $processingCount = $response->json('status.processingCount');
            $productCode = $response->json('body.0.productCode');
            // Check bidErrNo when processing is complete
            if ($bidErrNo === '999' && $processingCount == 0) {
                return true;
            }

            // Check for unavailable product codes
            if (in_array($bidErrNo, BursamErrorCode::UNAVAILABLE_PRODUCT_ERROR_CODES)) {
                $this->logError('Unavailable product code detected', $response);
                $unavailableProductCodes = Cache::get('bursam_unavailable_product_codes', []);
                $unavailableProductCodes[] = $productCode;
                $unavailableProductCodes = array_values(array_unique($unavailableProductCodes));
                Cache::put('bursam_unavailable_product_codes', $unavailableProductCodes, now()->addMinutes(30));

                return false;
            }

            // Check processing count
            if ($processingCount > 0) {
                $this->logError('Processing count greater than 0', $response);

                return false;
            }
            // Check bidErrNo when processing is complete
            if ($bidErrNo !== '999' && $processingCount == 0) {
                $this->logError('bidErrNo is not 999 and processingCount is zero', $response);

                return false;
            }
        }

        return true;
    }

    public function validateFetchNYY($response): bool
    {
        if ($this->isValidResponse($response, 'fetch_nyy')) {
            $processingCount = $response->json('status.processingCount');
            $otcErrNo = $response->json('body.0.otcErrNo');
            $stbErrNo = $response->json('body.0.stbErrNo');

            if ($processingCount != 0 || $otcErrNo != '999' || $stbErrNo != '999') {
                $this->logError('NYY validation failed', $response);

                return false;
            }
        }

        return true;
    }

    private function logError(string $message, $response): void
    {
        $logData = array_merge([
            'financingOrderId' => $this->traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrder->id,
            'response' => $response ? $response : null,
            'time' => now(),
        ]);

        log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle($message, $this->traderOrder), $logData);
    }

    public function sellProduct()
    {
        $financingOrder = $this->traderOrder->order;

        $url = 'api/process/svc/bsas/order.json';
        $requestHeader = [
            'memberShortName' => config('trader.providers.bursam.member_short_name'),
            'uuid' => $this->traderOrder->uuid_two,
        ];

        $request = [
            [
                'serialNumber' => '1',
                'bidOption' => 'N',
                'otcOption' => 'Y',
                'stbOption' => 'Y',
                'productCode' => $this->traderOrder->product_code,
                'purchaseType' => 'P',
                'clientName' => '',
                'currency' => 'SAR',
                'bidValue' => (string) $financingOrder->amount->convertAndFormatByDecimal(),
                'valueDate' => now('Asia/Kuala_Lumpur')->format('Ymd'),
                'tenor' => '00090',
                'otcCounterParty' => $financingOrder->customer_name,
                'otcMurabaha' => '',
                'otcMurabahaValue' => (string) $financingOrder->selling_price->convertAndFormatByDecimal(),
                'eCertNo' => $this->traderOrder->reference,
            ],
        ];

        $response = $this->rateLimitRequest(
            fn () => $this->http()
                ->post(
                    $url,
                    [
                        'header' => $requestHeader,
                        'request' => $request,
                    ]
                )
        );

        log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('Malaysia Bursa sellProduct request', $this->traderOrder), [
            'financingOrderId' => $financingOrder->id,
            'traderOrderId' => $this->traderOrder->id,
            'url' => $url,
            'request' => $request,
            'headers' => $requestHeader,
            'response' => $response->json(),
            'statusCode' => $response->getStatusCode(),
        ]);

        return $response;
    }

    public function fetchBuyResult()
    {
        log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('bursa purchasing step => fetchBuyResult', $this->traderOrder), [
            'financingOrderId' => $this->traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrder->id,
            'time' => now()]);

        return $this->fetchOrderResult($this->traderOrder->uuid_one);
    }

    public function fetchSellResult()
    {
        return $this->fetchOrderResult($this->traderOrder->uuid_two);
    }

    private function fetchOrderResult($uuid)
    {
        log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('bursa purchasing step => fetchOrderResult', $this->traderOrder), [
            'financingOrderId' => $this->traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrder->id,
            'time' => now()]);
        $url = 'api/process/svc/bsas/orderResult.json';

        $requestHeader = [
            'memberShortName' => config('trader.providers.bursam.member_short_name'),
            'uuid' => $uuid,
        ];
        $request = [
            'serialNumbers' => '1',
            'forceYN' => 'Y',
            'maxWaitTime' => '10',
            'waitAllDoneYN' => 'Y',
        ];

        $response = $this->rateLimitRequest(
            fn () => $this->http()
                ->post(
                    $url,
                    [
                        'header' => $requestHeader,
                        'request' => $request,
                    ]
                )
        );

        log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('Malaysia Bursa fetchOrderResult request', $this->traderOrder), [
            'financingOrderId' => $this->traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrder->id,
            'url' => $url,
            'request' => $request,
            'headers' => $requestHeader,
            'response' => $response->json(),
            'statusCode' => $response->getStatusCode(),
        ]);

        return $response;
    }

    public function getBidXml()
    {
        log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('bursa purchasing step => getBidXml', $this->traderOrder), [
            'financingOrderId' => $this->traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrder->id,
            'time' => now()]);
        $url = 'api/process/svc/bsas/bidXML.json';
        $request = [
            'membershortname' => config('trader.providers.bursam.member_short_name'),
            'ecertno' => $this->traderOrder->reference,
        ];
        $response = $this->rateLimitRequest(
            fn () => $this->http()
                ->post(
                    $url,
                    [
                        'input' => $request,
                    ]
                )
        );

        log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('Malaysia Bursa getBidXml request', $this->traderOrder), [
            'financingOrderId' => $this->traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrder->id,
            'url' => $url,
            'request' => $request,
            'response' => $response->json(),
            'statusCode' => $response->getStatusCode(),
        ]);

        return $response;
    }

    public function getOtcXml()
    {
        $url = 'api/process/svc/bsas/otcXML.json';
        $request = [
            'membershortname' => config('trader.providers.bursam.member_short_name'),
            'ecertno' => $this->traderOrder->reference,
        ];
        $response = $this->rateLimitRequest(
            fn () => $this->http()
                ->post(
                    $url,
                    [
                        'input' => $request,
                    ]
                )
        );

        log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('Malaysia Bursa getOtcXml request', $this->traderOrder), [
            'financingOrderId' => $this->traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrder->id,
            'url' => $url,
            'request' => $request,
            'response' => $response->json(),
            'statusCode' => $response->getStatusCode(),
        ]);

        return $response;
    }

    public function getStbXml()
    {
        $url = 'api/process/svc/bsas/stbXML.json';
        $request = [
            'membershortname' => config('trader.providers.bursam.member_short_name'),
            'ecertno' => $this->traderOrder->reference,
        ];

        $response = $this->rateLimitRequest(
            fn () => $this->http()
                ->post(
                    $url,
                    [
                        'input' => $request,
                    ]
                )
        );

        log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('Malaysia Bursa getStbXml request', $this->traderOrder), [
            'financingOrderId' => $this->traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrder->id,
            'url' => $url,
            'request' => $request,
            'response' => $response->json(),
            'statusCode' => $response->getStatusCode(),
        ]);

        return $response;
    }

    private function http(): PendingRequest
    {
        $instance = Http::bursam();
        $lastRequest = [
            'url' => '',
            'headers' => [],
            'body' => '',
            'start_time' => '',
            'end_time' => '',
            'method' => '',
        ];

        foreach ($this->middlewares as $middleware) {
            $instance->withMiddleware($middleware);
        }

        $lastRequest = [
            'url' => '',
            'headers' => [],
            'body' => '',
            'start_time' => '',
            'method' => '',
        ];

        $instance->beforeSending(function ($request) use (&$lastRequest) {
            $lastRequest = [
                'start_time' => Carbon::now()->format('Y-m-d H:i:s.u'),
                'url' => (string) $request->url(),
                'headers' => $request->headers(),
                'body' => (string) $request->body(),
                'method' => $request->method(),
            ];
        });
        $instance->throw(function ($response, $e) use (&$lastRequest) {
            log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('Error in request with BURSAM', $this->traderOrder), [
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'status_code' => $response->status(),
                'url' => $lastRequest['url'] ?? '',
                'method' => $lastRequest['method'] ?? '',
                'duration' => $lastRequest['duration'] ?? '',
                'start_time' => $lastRequest['start_time'] ?? '',
                'end_time' => Carbon::now()->format('Y-m-d H:i:s.u') ?? '',
                'request_headers' => $lastRequest['headers'] ?? [],
                'request_body' => $lastRequest['body'] ?? '',
                'exception' => [
                    'type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);
        });

        // Return the instance to continue the request
        return $instance;
    }

    // TODO : remove RateLimitExceededException and the catch and add log  insteadof fire exception
    protected function rateLimitRequest($callback, $remainingRetries = 0)
    {
        try {
            $maxRetriesBeforeException = (int) config('trader.providers.bursam.rate_limit.max_retries_before_exception');
            $decaySeconds = (int) config('trader.providers.bursam.rate_limit.decay_seconds');
            $maxAttempts = (int) config('trader.providers.bursam.rate_limit.max_attempts');

            if ($remainingRetries > $maxRetriesBeforeException) {
                $exception = new RateLimitExceededException('bursam_api');

                $exception->setContext([
                    'trader_order_id' => $this->traderOrder->id,
                    'provider' => $this->traderOrder->provider,
                    'version' => $this->traderOrder->version,
                    'remaining_retries' => $remainingRetries,
                    'max_retries_before_exception' => $maxRetriesBeforeException,
                ]);
                log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('bursa Reached the maximum number of allowed retries', $this->traderOrder), [
                    'financingOrderId' => $this->traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrder->id,
                    'remainingRetries' => $remainingRetries,
                    'maxRetriesBeforeException' => $maxRetriesBeforeException,
                ]);
                throw $exception;
            }
            log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('bursa send request rate limit', $this->traderOrder), [
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'time' => now(),
            ]);
            $executed = RateLimiter::attempt(
                'bursam_api',
                $maxAttempts,
                $callback,
                $decaySeconds,
            );

            if ($executed instanceof Response && empty($executed->json())) {
                log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('bursa API returned null response', $this->traderOrder), [
                    'financingOrderId' => $this->traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrder->id,
                    'remainingRetries' => $remainingRetries,
                    'response' => $executed,
                ]);
                throw new Exception('bursa API returned null response');
            }

            if ($executed === false) {
                log::channel(LOG_CHANNEL_BURSAM)->warning(formatLogTitle('bursa Rate limit exceeded, delaying retry without incrementing retries', $this->traderOrder), [
                    'financingOrderId' => $this->traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrder->id,
                    'remainingRetries' => $remainingRetries,
                    'callback' => $callback,
                    'executed' => $executed,
                ]);

                sleep($decaySeconds + 1);

                return $this->rateLimitRequest($callback, $remainingRetries);
            }

            return $executed;
        } catch (RateLimitExceededException $e) {
            log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('bursa RateLimitExceededException FUll ', $this->traderOrder), [
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'decaySeconds' => $decaySeconds,
                'remainingRetries' => $remainingRetries,
                'maxRetriesBeforeException' => $maxRetriesBeforeException,
            ]);
        } catch (Exception $e) {
            log::channel(LOG_CHANNEL_BURSAM)->error('bursa Bursam exception occurred', [
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace_string' => $e->getTraceAsString(),
                'decaySeconds' => $decaySeconds,
                'remainingRetries' => $remainingRetries,
                'maxRetriesBeforeException' => $maxRetriesBeforeException,
            ]);

            return $this->rateLimitRequest($callback, ++$remainingRetries);
        }
    }

    private function registerFakeBursamResponses()
    {
        try {
            Http::fake([
                $this->buildUrl('/api/process/svc/bsas/order.json') => function (Request $request) {
                    return Http::response([
                        'header' => [
                            'memberShortName' => 'BANKXYZ',
                            'uuid' => '550e8400-e29b-41d4-a716-i5jts0bkad',
                            'errorCode' => '',
                            'errorMsg' => '',
                        ],
                        'body' => [
                            [
                                'serialNumber' => '1',
                                'statusCode' => 0,
                                'statusMessage' => '',
                            ],
                        ],
                    ]);
                },
                $this->buildUrl('/api/process/svc/bsas/orderResult.json') => function (Request $request) {
                    $traderOrder = $this->getTraderOrderUsingFakeRequest($request);

                    return Http::response([
                        'status' => [
                            'totalOrderCount' => 1,
                            'processingCount' => 1,
                        ],
                        'body' => [
                            // [
                            //     'bidErrNo' => '999',
                            //     'serialNumber' => 1,
                            //     'bidOption' => $traderOrder->uuid_two ? 'N' : 'Y',
                            //     'otcOption' => $traderOrder->uuid_two ? 'Y' : 'N',
                            //     'stbOption' => $traderOrder->uuid_two ? 'Y' : 'N',
                            //     'productCode' => $traderOrder->product_code,
                            //     'purchaseType' => 'P',
                            //     'clientName' => '',
                            //     'currency' => 'SAR',
                            //     'bidValue' => (string) $traderOrder->order->amount->convertAndFormatByDecimal(),
                            //     'valueDate' => now()->format('Ymd'),
                            //     'tenor' => '00074',
                            //     'otcCounterParty' => 'TIOMAN',
                            //     'otcMurabaha' => '',
                            //     'otcMurabahaValue' => '',
                            //     'ecertNo' => strtoupper(Str::random()),
                            //     'bidErrNo' => '999',
                            //     'bidMsg' => 'OK',
                            //     'otcErrNo' => '999',
                            //     'otcMsg' => 'OK',
                            //     'stbErrNo' => '999',
                            //     'stbMsg' => 'OK',
                            //     'regTime' => now()->format('YmdHis'),
                            //     'orderTime' => now()->format('YmdHis'),
                            //     'resultTime' => now()->format('YmdHis'),
                            //     'purchaseTime' => now()->format('YmdHis'),
                            //     'reportTime' => now()->format('YmdHis'),
                            //     'sellingTime' => now()->format('YmdHis'),
                            //     'unit' => 'Tonnage',
                            //     'price' => (float) $traderOrder->order->amount->convertAndFormatByDecimal(),
                            // ],
                        ],
                    ]);
                },
                $this->buildUrl('/api/process/svc/bsas/bidXML.json') => function (Request $request) {
                    $traderOrder = $this->getTraderOrderUsingFakeRequest($request);

                    return Http::response([
                        'SUCCESSYN' => 'Y',
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
                        'SUCCESSYN' => 'Y',
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
        } catch (Exception $e) {
            log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('error at BursamClient - registerFakeBursamResponses', $this->traderOrder), [
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
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
