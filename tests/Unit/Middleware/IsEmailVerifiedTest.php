<?php

namespace Tests\Unit\Middleware;

use App\Actions\GetSettingsClassInstanceAction;
use App\Enums\Area;
use App\Http\Middleware\IsEmailVerified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class IsEmailVerifiedTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_email_is_not_verified()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('You must verify your email address');

        $this->user->email_verified_at = null;
        $this->user->save();

        $this->actingAs($this->user);

        $request = new Request();

        $request->setUserResolver(function () {
            return $this->user;
        });

        $middleware = new IsEmailVerified(new GetSettingsClassInstanceAction());

        $middleware->handle($request, function ($request) {
        }, Area::Lender);
    }

    public function test_pass_user_in_area_with_email_verified_not_required()
    {
        $this->user->email_verified_at = null;
        $this->user->save();

        $this->actingAs($this->user);

        $request = new Request();

        $request->setUserResolver(function () {
            return $this->user;
        });

        $middleware = new IsEmailVerified(new GetSettingsClassInstanceAction());

        $middleware->handle($request, function ($request) {
            $settingClass = (new GetSettingsClassInstanceAction())->handle(Area::SuperAdmin);
            $this->assertFalse(
                isset($settingClass->email_verification_enabled) &&
                $settingClass->email_verification_enabled
            );
        }, Area::SuperAdmin);
    }

    public function test_user_email_is_verified_with_no_auth_user()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('You must verify your email address');

        $request = new Request();

        $middleware = new IsEmailVerified(new GetSettingsClassInstanceAction());

        $middleware->handle($request, function ($request) {
        }, Area::Lender);
    }

    public function test_user_email_is_verified()
    {
        $this->actingAs($this->user);

        $request = new Request();

        $request->setUserResolver(function () {
            return $this->user;
        });

        $middleware = new IsEmailVerified(new GetSettingsClassInstanceAction());

        $middleware->handle($request, function ($request) {
            $this->assertNotNull($request->user()->email_verified_at);
        }, Area::Lender);
    }
}
