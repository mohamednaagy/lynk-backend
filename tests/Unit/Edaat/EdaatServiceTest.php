<?php

namespace Tests\Unit\Edaat;

use App\Support\Edaat\EdaatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    public function testThatCreateInvoiceSuccessIfIdIsUnique(): void
    {
        $response = self::$edaatService->createInvoice(self::$randomString, self::$amount);
        $this->assertNotNull($response);
        $this->assertIsString($response);
    }

    /**
     * @return void
     */
    public function testThatCreateInvoiceFailIfIdIsNotUnique(): void
    {
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
    public function testThatInvoiceIsNotPaid(): void
    {
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
    public function testThatRegisterWebhookReturnSuccess(): void
    {
        $response = self::$edaatService->registerWebhook(null, null, null);
        $this->assertNotNull($response);
        $this->assertIsBool($response);
        $this->assertTrue($response);
    }
}
