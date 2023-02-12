<?php

namespace Tests\Feature\Endpoints\Api\V1\Edaat;

use App\Enums\Role;
use App\Models\Company;
use App\Models\EdaatInvoice;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\EdaatInvoiceTransformer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class IndexInvoiceTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static User $userLender;

    private static Wallet $wallet;

    private static Builder|Model $edaatInvoice;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$edaatInvoice = $this->createEdaatInvoice(self::$company->id, self::$userLender->id);

        EdaatInvoice::factory(5)->create([
            'company_id' => self::$company->id,
            'creator_id' => self::$userLender->id,
        ]);
    }

    public function test_un_auth_user_cant_index_edaat_invoices_with_valid_data()
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->getJson('api/v1/lender/edaat-invoices')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_auth_user_can_index_edaat_invoices_with_valid_data()
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->getJson('api/v1/lender/edaat-invoices')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(EdaatInvoice::query()->latest()->paginate(), new EdaatInvoiceTransformer())
                    ->parseIncludes([
                        'id',
                        'invoice_number',
                        'amount',
                        'amount_formatted',
                        'company_name',
                        'company_number',
                        'status',
                        'created_at',
                    ])->respond()->getData(true)
            );
    }

    public function test_auth_user_can_index_edaat_invoices_as_oldest_sorting_succeed()
    {
        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->getJson('api/v1/lender/edaat-invoices?oldest=1')
            ->assertStatus(Response::HTTP_OK);

        $this->assertEquals(
            fractal(EdaatInvoice::query()->oldest()->paginate(), new EdaatInvoiceTransformer())
                ->parseIncludes([
                    'id',
                    'invoice_number',
                    'amount',
                    'amount_formatted',
                    'company_name',
                    'company_number',
                    'status',
                    'created_at',
                ])
                ->respond()
                ->getData(true),
            $response->json()
        );
    }

    public function test_auth_user_can_index_edaat_invoices_without_oldest_query_params_will_sort_by_latest()
    {
        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->getJson('api/v1/lender/edaat-invoices')
            ->assertStatus(Response::HTTP_OK);

        $this->assertNotEquals(
            fractal(EdaatInvoice::query()->oldest()->paginate(), new EdaatInvoiceTransformer())
                ->parseIncludes([
                    'id',
                    'invoice_number',
                    'amount',
                    'amount_formatted',
                    'company_name',
                    'company_number',
                    'status',
                    'created_at',
                ])
                ->respond()
                ->getData(true),
            $response->json()
        );
    }

    public function test_auth_user_can_index_edaat_invoices_as_latest_sorting_succeed()
    {
        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->getJson('api/v1/lender/edaat-invoices?oldest=0')
            ->assertStatus(Response::HTTP_OK);

        $this->assertEquals(
            fractal(EdaatInvoice::query()->latest()->paginate(), new EdaatInvoiceTransformer())
                ->parseIncludes([
                    'id',
                    'invoice_number',
                    'amount',
                    'amount_formatted',
                    'company_name',
                    'company_number',
                    'status',
                    'created_at',
                ])
                ->respond()
                ->getData(true),
            $response->json()
        );
    }
}
