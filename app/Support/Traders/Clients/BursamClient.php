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
        Log::channel('bursam')->info('Initializing BursamClient', [
            'trader_order_id' => $traderOrder->id,
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

        Log::channel('bursam')->info('bursa purchasing step => buy product', [
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
                'bidValue' => (float) $financingOrder->amount->convertAndFormatByDecimal(),
                'valueDate' => now('Asia/Kuala_Lumpur')->format('Ymd'),
                'tenor' => config('trader.providers.bursam.tenor'),
                'otcCounterParty' => $financingOrder->customer_name,
                'otcMurabaha' => '',
                'otcMurabahaValue' => (float) $financingOrder->selling_price->convertAndFormatByDecimal(),
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

        Log::channel('bursam')->info('Malaysia Bursa buyProduct request: ...'.json_encode([
            'url' => $url,
            'request' => $request,
            'headers' => $requestHeader,
            'response' => $response->json(),
            'statusCode' => $response->getStatusCode(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

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

        switch ($context) {
            case 'fetch_ynn':
                return $this->validateFetchYNN($response);
            case 'fetch_nyy':
                return $this->validateFetchNYY($response);
            default:
                return true;
        }
    }

    private function validateFetchYNN($response): bool
    {
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

        return true;
    }

    private function validateFetchNYY($response): bool
    {
        $processingCount = $response->json('status.processingCount');
        $otcErrNo = $response->json('body.0.otcErrNo');
        $stbErrNo = $response->json('body.0.stbErrNo');

        if ($processingCount != 0 || $otcErrNo != '999' || $stbErrNo != '999') {
            $this->logError('NYY validation failed', $response);

            return false;
        }

        return true;
    }

    private function logError(string $message, $response): void
    {
        $logData = array_merge([
            'traderOrderId' => $this->traderOrder->id,
            'financingOrderId' => $this->traderOrder->order->id,
            'response' => $response ? $response : null,
            'time' => now(),
        ]);

        Log::channel('bursam')->error($message, $logData);
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
                'bidValue' => (float) $financingOrder->amount->convertAndFormatByDecimal(),
                'valueDate' => now('Asia/Kuala_Lumpur')->format('Ymd'),
                'tenor' => '00090',
                'otcCounterParty' => $financingOrder->customer_name,
                'otcMurabaha' => '',
                'otcMurabahaValue' => (float) $financingOrder->selling_price->convertAndFormatByDecimal(),
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

        Log::channel('bursam')->info('Malaysia Bursa sellProduct request: ...'.json_encode([
            'url' => $url,
            'request' => $request,
            'headers' => $requestHeader,
            'response' => $response->json(),
            'statusCode' => $response->getStatusCode(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $response;
    }

    public function fetchBuyResult()
    {
        Log::channel('bursam')->info('bursa purchasing step => fetchBuyResult', [
            'financingOrderId' => $this->traderOrder->order->id,
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
        Log::channel('bursam')->info('bursa purchasing step => fetchOrderResult', [
            'financingOrderId' => $this->traderOrder->order->id,
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

        Log::channel('bursam')->info('Malaysia Bursa fetchOrderResult request: ...'.json_encode([
            'url' => $url,
            'request' => $request,
            'headers' => $requestHeader,
            'response' => $response->json(),
            'statusCode' => $response->getStatusCode(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $response;
    }

    public function getBidXml()
    {
        Log::channel('bursam')->info('bursa purchasing step => getBidXml', [
            'financingOrderId' => $this->traderOrder->order->id,
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

        Log::channel('bursam')->info('Malaysia Bursa getBidXml request: ...'.json_encode([
            'url' => $url,
            'request' => $request,
            'response' => $response->json(),
            'statusCode' => $response->getStatusCode(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

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

        Log::channel('bursam')->info('Malaysia Bursa getOtcXml request: ...'.json_encode([
            'url' => $url,
            'request' => $request,
            'response' => $response->json(),
            'statusCode' => $response->getStatusCode(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

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

        Log::channel('bursam')->info('Malaysia Bursa getStbXml request: ...'.json_encode([
            'url' => $url,
            'request' => $request,
            'response' => $response->json(),
            'statusCode' => $response->getStatusCode(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

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
            Log::channel('bursam')->error('Error in request with BURSAM', [
                'message' => $e->getMessage(),
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

    //TODO : remove RateLimitExceededException and the catch and add log  insteadof fire exception
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
                Log::channel('bursam')->error('bursa Reached the maximum number of allowed retries', [
                    'remainingRetries' => $remainingRetries,
                    'maxRetriesBeforeException' => $maxRetriesBeforeException,
                ]);
                throw $exception;
            }
            Log::channel('bursam')->info('bursa send request rate limit', [
                'time' => now(),
                'order' => $this->traderOrder->order->id,
                'trader_order_id' => $this->traderOrder->id]);
            $executed = RateLimiter::attempt(
                'bursam_api',
                $maxAttempts,
                $callback,
                $decaySeconds,
            );

            if ($executed instanceof Response && empty($executed->json())) {
                Log::channel('bursam')->error('bursa API returned null response', [
                    'traderOrderId' => $this->traderOrder->id,
                    'financingOrderId' => $this->traderOrder->order->id,
                    'remainingRetries' => $remainingRetries,
                    'response' => $executed,
                ]);
                throw new Exception('bursa API returned null response');
            }

            if ($executed === false) {
                Log::channel('bursam')->warning('bursa Rate limit exceeded, delaying retry without incrementing retries', [
                    'remainingRetries' => $remainingRetries,
                    'callback' => $callback,
                    'executed' => $executed,
                    'order' => $this->traderOrder->order->id,
                    'trader_order_id' => $this->traderOrder->id,
                ]);

                sleep($decaySeconds + 1);

                return $this->rateLimitRequest($callback, $remainingRetries);
            }

            return $executed;
        } catch (RateLimitExceededException $e) {
            Log::channel('bursam')->error('bursa RateLimitExceededException FUll ', [
                'message' => $e->getMessage(),
                'decaySeconds' => $decaySeconds,
                'remainingRetries' => $remainingRetries,
                'maxRetriesBeforeException' => $maxRetriesBeforeException,
            ]);
        } catch (Exception $e) {
            Log::channel('bursam')->error('bursa Bursam exception occurred', [
                'traderOrderId' => $this->traderOrder->id,
                'financingOrderId' => $this->traderOrder->order->id,
                'message' => $e->getMessage(),
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
                            'processingCount' => 0,
                        ],
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
            Log::channel('bursam')->error($e->getMessage(), ['line' => $e->getLine(), 'file' => $e->getFile()]);
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
