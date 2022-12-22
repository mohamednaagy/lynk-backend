<?php

namespace Tests\Unit\Generators;

use App\Support\PdfGenerator\Exceptions\GeneratingPdfException;
use App\Support\PdfGenerator\Exceptions\MissingStorageCallbackException;
use App\Support\PdfGenerator\Generators\BrowserlessGenerator;
use App\Support\PdfGenerator\PdfGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use function Psl\Type\mixed;
use Tests\TestCase;

class PdfGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected static string $pdfGenerator;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_second_argument_can_be_a_closure_or_callback_function()
    {
        PdfGenerator::shouldReceive('outputFromHtml')
            ->once()
            ->with('<h1>Hi</h1>', \Mockery::on(function ($options) {
                return is_callable($options);
            }))->andReturn(mixed());

        PdfGenerator::outputFromHtml('<h1>Hi</h1>', function () {
        });
    }

    public function test_second_argument_can_be_a_array()
    {
        PdfGenerator::shouldReceive('outputFromHtml')
            ->once()
            ->with('<h1>Hi</h1>', \Mockery::on(function ($options) {
                return is_array($options);
            }))->andReturn(mixed());

        PdfGenerator::outputFromHtml('<h1>Hi</h1>', []);
    }

    public function test_second_argument_not_provided_throw_exception()
    {
        $this->expectException(MissingStorageCallbackException::class);

        PdfGenerator::outputFromHtml('<h1>Hi</h1>');
    }

    public function test_second_argument_is_array_and_key_storage_callback_not_provided_throw_exception()
    {
        $this->expectException(MissingStorageCallbackException::class);

        PdfGenerator::outputFromHtml('<h1>Hi</h1>', []);
    }

    public function test_second_argument_not_array_and_not_callable_will_throw_exception()
    {
        $this->expectException(MissingStorageCallbackException::class);

        PdfGenerator::outputFromHtml('<h1>Hi</h1>', 'test');
    }

    public function test_fails_generate_pdf_will_throw_exception()
    {
        $this->expectException(GeneratingPdfException::class);
        //make sure the server is running
        $browserlessGenerator = new BrowserlessGenerator([
            'base_url' => Config::get('app.url'),
            'storage_disk' => 'test_disk',
        ]);

        $browserlessGenerator->outputFromHtml('<h1>Hi</h1>', function () {
            // dummy closure for test
        });
    }
}
