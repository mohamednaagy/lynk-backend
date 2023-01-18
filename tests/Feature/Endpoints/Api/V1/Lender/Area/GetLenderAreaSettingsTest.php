<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Area;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Area;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\LenderSettingsTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class GetLenderAreaSettingsTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userLenderAdmin;

    private static $settings;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$settings = $this->app->make(GetSettingsClassInstance::class)
            ->handle(Area::Lender);
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_can_get_area_settings(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/area-settings')
            ->assertOk()
            ->assertExactJson(
                fractal(self::$settings, new LenderSettingsTransformer())
                    ->parseIncludes(['email_verification_enabled'])
                    ->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_can_get_area_settings(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/area-settings')
            ->assertOk()
            ->assertExactJson(
                fractal(self::$settings, new LenderSettingsTransformer())
                    ->parseIncludes(['email_verification_enabled'])
                    ->respond()
                    ->getData(true)
            );
    }
}
