<?php

namespace Tests\Unit\Edaat;

use App\Support\Edaat\EdaatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class EdaatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected static EdaatService $edaatService;

    protected static string $randomString;

    protected static int $amount;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        self::$edaatService = new EdaatService();
        self::$randomString = Str::random(10);
        self::$amount = 100;
    }

    /**
     * @return void
     */
    public function test_that_create_invoice_success_if_id_is_unique(): void
    {
        Http::fake(function () {
            return Http::response([
                'Status' => ['Success' => true, 'Code' => 'E000'],
                'Body' => ['InvoiceNo' => '1'],
            ]);
        });

        $response = self::$edaatService->createInvoice(self::$randomString, self::$amount);
        $this->assertNotNull($response);
        $this->assertEquals(1, $response);
    }

    /**
     * @return void
     */
    public function test_that_create_invoice_fail_if_id_is_not_unique(): void
    {
        Http::fakeSequence()
            ->push([
                'access_token' => 'token',
            ])
            ->push([
                'Status' => ['Success' => true, 'Code' => 'E000'],
                'Body' => ['InvoiceNo' => '1'],
            ])->push([
                'Status' => ['Success' => false],
            ]);

        $response = self::$edaatService->createInvoice(self::$randomString, self::$amount);
        $this->assertNotNull($response);
        $this->assertIsString($response);

        $response = self::$edaatService->createInvoice(self::$randomString, self::$amount);
        $this->assertFalse($response);
        $this->assertIsBool($response);
    }

    /**
     * @return void
     */
    public function test_that_invoice_is_not_paid(): void
    {
        Http::fakeSequence()
            ->push([
                'access_token' => 'token',
            ])
            ->push([
                'Status' => ['Success' => true, 'Code' => 'E000'],
                'Body' => ['InvoiceNo' => '1'],
            ])->push([
                'Status' => ['Success' => true, 'Code' => 'E000'],
                'Body' => ['StatusEn' => 'Unpaid'],
            ]);

        $response = self::$edaatService->createInvoice(self::$randomString, self::$amount);
        $this->assertNotNull($response);
        $this->assertIsString($response);

        $response = self::$edaatService->isPaidInvoice($response);
        $this->assertFalse($response);
        $this->assertIsBool($response);
    }

    /**
     * @return void
     */
    public function test_that_register_webhook_return_success(): void
    {
        Http::fake(function () {
            return Http::response([
                'Status' => ['Success' => true, 'Code' => 'E000'],
            ]);
        });

        $response = self::$edaatService->registerWebhook(
            config('edaat.payment_url'),
            config('edaat.bill_url'),
            config('edaat.reconcile_url')
        );

        $this->assertNotNull($response);
        $this->assertIsBool($response);
        $this->assertTrue($response);
    }
}
