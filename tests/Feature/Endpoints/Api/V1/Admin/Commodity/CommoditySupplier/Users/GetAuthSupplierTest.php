<?php

namespace Endpoints\Api\V1\Admin\Commodity\CommoditySupplier\Users;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCommoditySupplier;
use Tests\Traits\InteractsWithUser;

class GetAuthSupplierTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCommoditySupplier;

    private static User $supplierAdmin;

    private static User $SupplierApiAdmin;

    private static Company $company;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$company = $this->createCommoditySupplier(
            'new legal name'.rand(11, 999),
            'new unique name'.rand(11, 999),
            'test description'.rand(11, 999)
        );
        self::$supplierAdmin = $this->createSupplierUser(self::$company->id, Role::SupplierAdmin);
        self::$SupplierApiAdmin = $this->createSupplierUser(self::$company->id, Role::SupplierApiAdmin);
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_fetch_his_details(): void
    {
        $this->withHeaders([
            'X-Company' => self::$company->id,
        ])->getJson('api/v1/supplier/auth')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    

    /**
     * @return void
     */
    public function test_that_supplier_admin_can_fetch_his_details(): void
    {    
        $this->actingAs(self::$supplierAdmin)
        ->withHeaders([
            'X-Company' => self::$company->id,
        ])->getJson('api/v1/supplier/auth')
        ->assertStatus(Response::HTTP_OK)
        ->assertExactJson(
            fractal(self::$supplierAdmin, new UserTransformer(Area::CommoditySupplier))
                ->parseIncludes([
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'is_email_verified',
                    'role',
                    'company.id',
                    'company.name',
                    'company.status',
                    'company.unique_name',
                    'permissions',
                    'locale',
                    'phone_number',
                    'phone_country_code',
                    'formatted_phone_number',
                ])->respond()->getData(true)
        );
    }

    /**
     * @return void
     */
    public function test_that_supplier_api_user_can_fetch_his_details(): void
    {
        $this->actingAs(self::$SupplierApiAdmin)
        ->withHeaders([
            'X-Company' => self::$company->id,
        ])->getJson('api/v1/supplier/auth')
        ->assertStatus(Response::HTTP_OK)
        ->assertExactJson(
            fractal(self::$SupplierApiAdmin, new UserTransformer(Area::CommoditySupplier))
                ->parseIncludes([
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'is_email_verified',
                    'role',
                    'company.id',
                    'company.name',
                    'company.status',
                    'company.unique_name',
                    'permissions',
                    'locale',
                    'phone_number',
                    'phone_country_code',
                    'formatted_phone_number',
                ])->respond()->getData(true)
        );
    }
}
