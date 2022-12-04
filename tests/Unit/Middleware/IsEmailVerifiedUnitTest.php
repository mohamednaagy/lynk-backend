<?php

namespace Tests\Unit\Middleware;

use App\Actions\GetSettingsClassInstanceAction;
use App\Enums\Area;
use App\Enums\Role;
use App\Http\Middleware\IsEmailVerified;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class IsEmailVerifiedUnitTest extends TestCase
{
    use RefreshDatabase , InteractsWithLender;

    private static Company $company;

    private static User $userLenderAdmin;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        [self::$company] = $this->createCompany();
        self::$userLenderAdmin = $this->createLenderUser(self::$company->getOriginal('id'), Role::LenderAdmin);
    }

    public function test_is_email_verified_throws_exception_if_email_is_not_verified_with_area_that_requires_email_verification()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage(__('error.must_verify_email'));

        self::$userLenderAdmin->email_verified_at = null;
        self::$userLenderAdmin->save();

        $this->actingAs(self::$userLenderAdmin);

        $request = new Request();

        $request->setUserResolver(function () {
            return self::$userLenderAdmin;
        });

        $settingsClass = new GetSettingsClassInstanceAction();
        $middleware = new IsEmailVerified($settingsClass);

        foreach (Area::getValues() as $key => $area) {
            $areaSettingsClass = $settingsClass->handle($area);
            if (isset($areaSettingsClass->email_verification_enabled)) {
                $areaSettingsClass->email_verification_enabled = true;
                $areaSettingsClass->save();

                $middleware->handle($request, function ($request) {
                }, $area);
            }
        }
    }

    public function test_is_email_verified_does_not_throw_exception_if_email_verification_is_not_required()
    {
        self::$userLenderAdmin->email_verified_at = null;
        self::$userLenderAdmin->save();

        $this->actingAs(self::$userLenderAdmin);

        $request = new Request();

        $request->setUserResolver(function () {
            return self::$userLenderAdmin;
        });

        $settingsClass = new GetSettingsClassInstanceAction();
        $middleware = new IsEmailVerified($settingsClass);

        foreach (Area::getValues() as $key => $area) {
            $areaSettingsClass = $settingsClass->handle($area);
            if (isset($areaSettingsClass->email_verification_enabled)) {
                $areaSettingsClass->email_verification_enabled = false;
                $areaSettingsClass->save();

                $response = $middleware->handle($request, function ($request) {
                    return true;
                }, $area);
                $this->assertTrue((bool) $response);
            }
        }
    }

    public function test_is_email_verified_throws_exception_if_user_is_not_auth()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage(__('error.must_verify_email'));

        $request = new Request();

        $middleware = new IsEmailVerified(new GetSettingsClassInstanceAction());

        $middleware->handle($request, function ($request) {
        }, Area::Lender);
    }
}
