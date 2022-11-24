<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SetLocalization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SetLocalizationUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_localization_from_header_x_locale()
    {
        $this->actingAs($this->user);

        $request = new Request();

        $xLocale = 'fr';
        $request->headers->add(['X-Locale' => $xLocale]);

        $request->setUserResolver(function () {
            return $this->user;
        });

        $middleware = new SetLocalization();

        $middleware->handle($request, function ($request) use ($xLocale) {
            $this->assertTrue(app()->getLocale() == $xLocale);
        });
    }

    public function test_set_localization_from_user_preferred_locale()
    {
        $this->user->locale = 'fr';
        $this->user->save();

        $this->actingAs($this->user);

        $request = new Request();

        $request->setUserResolver(function () {
            return $this->user;
        });

        $middleware = new SetLocalization();

        $middleware->handle($request, function ($request) {
            $this->assertTrue(app()->getLocale() == $request->user()->locale);
        });
    }

    public function test_set_localization_as_default_config()
    {
        $this->actingAs($this->user);

        $request = new Request();

        $request->setUserResolver(function () {
            return $this->user;
        });

        $middleware = new SetLocalization();

        $middleware->handle($request, function ($request) {
            $this->assertTrue(app()->getLocale() == config('app.locale'));
        });
    }
}
