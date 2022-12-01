<?php

namespace Tests\Unit\Middlewares;

use App\Enums\Role;
use App\Http\Middleware\SetLocalization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class SetLocalizationUnitTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static User $userLenderAdmin;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        self::$userLenderAdmin = $this->createLenderUser(null, Role::LenderAdmin);
    }

    public function test_set_localization_from_not_supported_header_x_locale()
    {
        $this->actingAs(self::$userLenderAdmin);

        $request = new Request();

        $locale = 'fr';
        $request->headers->add(['X-Locale' => $locale]);

        $request->setUserResolver(function () {
            return self::$userLenderAdmin;
        });

        $middleware = new SetLocalization();

        $middleware->handle($request, function ($request) {
        });
        $this->assertFalse(app()->getLocale() == $locale);
    }

    public function test_set_localization_from_supported_header_x_locale()
    {
        $this->actingAs(self::$userLenderAdmin);

        $request = new Request();

        $locale = 'en';
        $request->headers->add(['X-Locale' => $locale]);

        $request->setUserResolver(function () {
            return self::$userLenderAdmin;
        });

        $middleware = new SetLocalization();

        $middleware->handle($request, function ($request) {
        });
        $this->assertTrue(app()->getLocale() == $locale);
    }

    public function test_set_localization_from_user_preferred_locale()
    {
        self::$userLenderAdmin->locale = 'ar';
        self::$userLenderAdmin->save();

        $this->actingAs(self::$userLenderAdmin);

        $request = new Request();

        $request->setUserResolver(function () {
            return self::$userLenderAdmin;
        });

        $middleware = new SetLocalization();

        $middleware->handle($request, function ($request) {
        });
        $this->assertTrue(app()->getLocale() == $request->user()->locale);
    }

    public function test_set_localization_as_default_config()
    {
        $this->actingAs(self::$userLenderAdmin);

        $request = new Request();

        $request->setUserResolver(function () {
            return self::$userLenderAdmin;
        });

        $middleware = new SetLocalization();

        $middleware->handle($request, function ($request) {
            $this->assertTrue(app()->getLocale() == config('app.locale'));
        });
    }
}
