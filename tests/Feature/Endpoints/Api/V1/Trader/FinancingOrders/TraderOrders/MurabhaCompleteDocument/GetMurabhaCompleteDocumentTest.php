<?php

namespace Endpoints\Api\V1\Trader\FinancingOrders\TraderOrders\MurabhaCompleteDocument;

use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class GetMurabhaCompleteDocumentTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    const BaseUrl = 'api/v1/trader';

    private static Company $trader;

    private static Company $lender;

    private static User $userLender;

    private static User $traderAdminUser;

    private static Builder|Model $financingOrder;

    private static TraderOrder $traderOrder;

    private static string $getMurabhaCompleteDocumentUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$trader] = $this->createTraderCompany('2000', [
            'company_cr' => '1234567891',
        ]);
        [self::$lender] = $this->createLenderCompany('2000', [
            'company_cr' => '1234567892',
        ]);
        self::$traderAdminUser = $this->createTraderUser(self::$trader->id);
        self::$userLender = $this->createLenderUser(self::$lender->id);
        self::$financingOrder = $this->createOrder(
            self::$lender->id,
            self::$userLender->id,
            [
                'is_verification_required' => true,
                'status' => FinancingOrderStatus::InProgress,
            ]
        );

        // create trader order
        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);

        self::$getMurabhaCompleteDocumentUrl = self::BaseUrl.
            '/orders/'.
            self::$financingOrder->getOriginal('id').
            '/trader-orders/'.
            self::$traderOrder->getOriginal('id').
            '/murabha-complete';
    }

    /**
     * @return void
     */
    public function test_that_unauth_user_cant_get_murabha_complete_document(): void
    {
        $this->withHeader('X-Company', self::$trader->id)
            ->getJson(self::$getMurabhaCompleteDocumentUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_other_area_roles_of_not_trader_area_cant_get_murabha_complete_document(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [
                Area::Trader,
            ],
            function ($user, $role) {
                return $this->withHeader('X-Company', self::$trader->id)
                    ->actingAs($user)
                    ->getJson(self::$getMurabhaCompleteDocumentUrl);
            }
        );
    }

    /**
     * @return void
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function test_get_murabha_complete_document_succeed(): void
    {
        $fileName = self::$traderOrder->provider.'-'.self::$traderOrder->reference.'.pdf';
        self::$traderOrder->addMedia(
            UploadedFile::fake()
                ->image($fileName)
        )->toMediaCollection(TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo);

        $this->withHeader('X-Company', self::$trader->id)
            ->actingAs(self::$traderAdminUser)
            ->getJson(self::$getMurabhaCompleteDocumentUrl)
            ->assertJsonStructure([
                'data' => [
                    'url',
                ],
            ]);
    }
}
