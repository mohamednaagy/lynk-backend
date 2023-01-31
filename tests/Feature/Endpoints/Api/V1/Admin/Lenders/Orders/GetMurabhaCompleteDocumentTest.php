<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders\Orders;

use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class GetMurabhaCompleteDocumentTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    const BaseUrl = 'api/v1/admin/lenders/';

    private static Company $lender;

    private static User $userLender;

    private static User $superAdminUser;

    private static Builder|Model $financingOrder;

    private static TraderOrder $traderOrder;

    private static string $getMurabhaCompleteDocumentUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('module:seed');

        self::$superAdminUser = $this->createSuperAdminUser();
        [self::$lender] = $this->createLenderCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$lender->id);
        self::$financingOrder = $this->createOrder(
            self::$lender->id,
            self::$userLender->id,
            [
                'is_verification_required' => true,
                'status' => FinancingOrderStatus::CommodityPurchased,
            ]
        );
        self::$getMurabhaCompleteDocumentUrl = self::BaseUrl.
            self::$lender->getOriginal('id').
            '/orders/'.
            self::$financingOrder->getOriginal('id').
            '/murabha-complete';

        // create trader order
        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);
    }

    /**
     * @return void
     */
    public function test_that_unauth_user_cant_get_murabha_complete_document(): void
    {
        $this->getJson(self::$getMurabhaCompleteDocumentUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_other_area_roles_of_not_super_admin_area_cant_get_murabha_complete_document(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [
                Area::SuperAdmin,
            ],
            function ($user, $role) {
                return $this->actingAs($user)
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
        self::$financingOrder->addMedia(
            UploadedFile::fake()
                ->image($fileName)
        )->toMediaCollection(FinancingOrderMediaCollection::WarrantAmendmentExceptWarrantNo);

        $this->actingAs(self::$superAdminUser)
            ->getJson(self::$getMurabhaCompleteDocumentUrl)
            ->assertJsonStructure([
                'data' => [
                    'url',
                ],
            ]);
    }
}
