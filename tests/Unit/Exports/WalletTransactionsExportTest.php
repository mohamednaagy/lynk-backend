<?php

namespace Tests\Unit\Exports;

use App\Enums\WalletType;
use App\Exports\WalletTransactionsExport;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\TransactionUtilInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;

class WalletTransactionsExportTest extends TestCase
{
    use InteractsWithCompany, RefreshDatabase;

    private WalletTransactionsExport $export;

    private Request $request;

    private Builder $transactionsQuery;

    private Company $company;

    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->company, $this->wallet] = $this->createCompany(5000);
        $this->request = new Request;
        $this->transactionsQuery = $this->company->transactions(WalletType::CompanyWallet);

        $this->export = new WalletTransactionsExport(
            $this->request,
            $this->transactionsQuery,
            $this->company
        );
    }

    public function test_constructor_sets_properties_correctly(): void
    {
        $this->assertInstanceOf(WalletTransactionsExport::class, $this->export);
        $this->assertEquals($this->request, $this->getPrivateProperty($this->export, 'request'));
        $this->assertEquals($this->transactionsQuery, $this->getPrivateProperty($this->export, 'transactionsQuery'));
        $this->assertEquals($this->company, $this->getPrivateProperty($this->export, 'company'));
    }

    public function test_chunk_size_returns_correct_value(): void
    {
        $this->assertEquals(5000, $this->export->chunkSize());
    }

    public function test_set_excludes_sets_excludes_array(): void
    {
        $excludes = ['date', 'transaction_amount'];
        $result = $this->export->setExcludes($excludes);

        $this->assertInstanceOf(WalletTransactionsExport::class, $result);
        $this->assertEquals($excludes, $this->getPrivateProperty($this->export, 'excludes'));
    }

    public function test_set_excludes_returns_static_instance(): void
    {
        $result = $this->export->setExcludes(['date']);
        $this->assertSame($this->export, $result);
    }

    public function test_headings_returns_all_headings_when_no_excludes(): void
    {
        $expectedHeadings = [
            'Transaction Date',
            'Transaction Description',
            'Transaction Amount',
            'Remaining Amount Balance',
        ];

        $this->assertEquals($expectedHeadings, $this->export->headings());
    }

    public function test_headings_filters_excluded_columns(): void
    {
        $this->export->setExcludes(['date', 'transaction_amount']);

        $expectedHeadings = [
            'Transaction Description',
            'Remaining Amount Balance',
        ];

        $this->assertEquals($expectedHeadings, $this->export->headings());
    }

    public function test_generator_yields_mapped_transactions(): void
    {
        // Create test transactions
        $transaction1 = $this->createTestTransaction(1000, 5000, 'Test reason 1');
        $transaction2 = $this->createTestTransaction(500, 4500, 'Test reason 2');

        // Mock the TransactionUtilInterface
        $transactionUtil = Mockery::mock(TransactionUtilInterface::class);
        $transactionUtil->shouldReceive('getDescription')
            ->with($transaction1)
            ->andReturn('Test description 1');
        $transactionUtil->shouldReceive('getDescription')
            ->with($transaction2)
            ->andReturn('Test description 2');

        $this->app->instance(TransactionUtilInterface::class, $transactionUtil);

        $generator = $this->export->generator();
        $results = iterator_to_array($generator);

        $this->assertCount(2, $results);
        $this->assertIsArray($results[0]);
        $this->assertCount(4, $results[0]); // All 4 columns
    }

    public function test_generator_respects_excludes(): void
    {
        $this->export->setExcludes(['date', 'transaction_amount']);

        $transaction = $this->createTestTransaction(1000, 5000, 'Test reason');

        // Mock the TransactionUtilInterface
        $transactionUtil = Mockery::mock(TransactionUtilInterface::class);
        $transactionUtil->shouldReceive('getDescription')
            ->with($transaction)
            ->andReturn('Test description');

        $this->app->instance(TransactionUtilInterface::class, $transactionUtil);

        $generator = $this->export->generator();
        $results = iterator_to_array($generator);

        $this->assertCount(1, $results);
        $this->assertCount(2, $results[0]); // Only 2 columns after exclusion
    }

    public function test_map_formats_transaction_data_correctly(): void
    {
        $transaction = $this->createTestTransaction(1000, 5000, 'Test reason');

        // Mock the TransactionUtilInterface
        $transactionUtil = Mockery::mock(TransactionUtilInterface::class);
        $transactionUtil->shouldReceive('getDescription')
            ->with($transaction)
            ->andReturn('Test description');

        $this->app->instance(TransactionUtilInterface::class, $transactionUtil);

        $result = $this->callPrivateMethod($this->export, 'map', [$transaction]);

        $this->assertIsArray($result);
        $this->assertCount(4, $result);

        // Check date format (should be in Asia/Riyadh timezone)
        $expectedDate = $transaction->created_at->clone()->tz('Asia/Riyadh')->format('Y-m-d H:i:s');
        $this->assertEquals($expectedDate, $result[0]);

        // Check description
        $this->assertEquals('Test description', $result[1]);

        // Check that amounts are formatted
        $this->assertIsString($result[2]); // transaction amount
        $this->assertIsString($result[3]); // remaining balance
    }

    public function test_map_handles_null_reason(): void
    {
        $transaction = $this->createTestTransaction(1000, 5000, null);

        $result = $this->callPrivateMethod($this->export, 'map', [$transaction]);

        $this->assertIsArray($result);
        $this->assertNull($result[1]); // description should be null
    }

    public function test_filter_excludes_removes_excluded_keys(): void
    {
        $items = [
            'date' => '2023-01-01',
            'description' => 'Test',
            'amount' => '100.00',
            'balance' => '500.00',
        ];

        $this->export->setExcludes(['date', 'amount']);
        $result = $this->callPrivateMethod($this->export, 'filterExcludes', [$items]);

        $expected = [
            'description' => 'Test',
            'balance' => '500.00',
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_filter_excludes_returns_all_items_when_no_excludes(): void
    {
        $items = [
            'date' => '2023-01-01',
            'description' => 'Test',
            'amount' => '100.00',
            'balance' => '500.00',
        ];

        $result = $this->callPrivateMethod($this->export, 'filterExcludes', [$items]);

        $this->assertEquals($items, $result);
    }

    public function test_get_file_name_generates_correct_format(): void
    {
        // Set a fixed time for testing
        Carbon::setTestNow('2023-01-15 14:30:45');

        $fileName = $this->export->getFileName();

        $expectedPattern = '/^'.preg_quote(str_replace(' ', '', $this->company->name), '/').'_LYNKWalletTransactions_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.xlsx$/';
        $this->assertMatchesRegularExpression($expectedPattern, $fileName);

        // Reset Carbon
        Carbon::setTestNow();
    }

    public function test_get_file_name_removes_spaces_from_company_name(): void
    {
        // Create a company with spaces in the name
        $companyWithSpaces = Company::factory()->create(['name' => 'Test Company Name']);

        $export = new WalletTransactionsExport(
            $this->request,
            $this->transactionsQuery,
            $companyWithSpaces
        );

        $fileName = $export->getFileName();

        $this->assertStringContainsString('TestCompanyName_LYNKWalletTransactions_', $fileName);
        $this->assertStringNotContainsString(' ', explode('_LYNKWalletTransactions_', $fileName)[0]);
    }

    public function test_implements_required_interfaces(): void
    {
        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\FromGenerator::class, $this->export);
        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\WithCustomChunkSize::class, $this->export);
        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\WithHeadings::class, $this->export);
    }

    public function test_uses_localizable_trait(): void
    {
        $traits = class_uses_recursive(WalletTransactionsExport::class);
        $this->assertContains(\Illuminate\Support\Traits\Localizable::class, $traits);
    }

    /**
     * Create a test transaction for testing purposes
     */
    private function createTestTransaction(int $amount, int $balance, ?string $reason): Transaction
    {
        return Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'amount' => $amount,
            'balance' => $balance,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    /**
     * Get a private property value using reflection
     */
    private function getPrivateProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * Call a private method using reflection
     */
    private function callPrivateMethod(object $object, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
