<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ValidateSignature;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithUser;

class CompleteAdminRegisterTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser;

    private static User $adminUser;

    private static string $endpoint;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$adminUser = $this->createSuperAdminUser(Role::Admin, [
            'email' => 'Admin@bim.com',
            'password' => null,
            'email_verified_at' => null,
        ]);
        self::$endpoint = 'api/v1/admin/'.self::$adminUser->id.'/sign-up';
    }

    /**
     * @return void
     */
    public function test_complete_register_success(): void
    {
        $this->assertFalse(self::$adminUser->hasVerifiedEmail());
        $this->assertNull(self::$adminUser->password);

        $this->withoutMiddleware(ValidateSignature::class)
            ->postJson(self::$endpoint, [
                'first_name' => self::$adminUser->first_name,
                'last_name' => self::$adminUser->last_name,
                'password' => '123456789Aa$$',
                'password_confirmation' => '123456789Aa$$',
                'source' => 'test',
            ])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'company_id',
                    'token',
                    'type',
                ],
            ]);

        self::$adminUser = self::$adminUser->refresh();
        $this->assertTrue(self::$adminUser->hasVerifiedEmail());
        $this->assertNotNull(self::$adminUser->password);
    }

    /**
     * @return void
     */
    public function test_complete_register_fail_without_signature(): void
    {
        $this->postJson(self::$endpoint, [
            'first_name' => self::$adminUser->first_name,
            'last_name' => self::$adminUser->last_name,
            'password' => '123456789Aa$$',
            'password_confirmation' => '123456789Aa$$',
            'source' => 'test',
        ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonFragment([
                'message' => __('Invalid signature.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_complete_register_if_authenticated(): void
    {
        $this->actingAs(self::$adminUser)
            ->withoutMiddleware(ValidateSignature::class)
            ->postJson(self::$endpoint, [
                'first_name' => self::$adminUser->first_name,
                'last_name' => self::$adminUser->last_name,
                'password' => '123456789Aa$$',
                'password_confirmation' => '123456789Aa$$',
                'source' => 'test',
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', __('This action is unauthorized.'));
    }

    /**
     * @return void
     */
    public function test_complete_register_validation_rules(): void
    {
        $this->withoutMiddleware(ValidateSignature::class)
            ->postJson(self::$endpoint, [
                'password' => '123456789Aa$$',
                'password_confirmation' => '123456789Aa$$',
                'source' => 'test',
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('first_name')
            ->assertJsonValidationErrorFor('last_name');

        $this->withoutMiddleware(ValidateSignature::class)
            ->postJson(self::$endpoint, [
                'first_name' => self::$adminUser->first_name,
                'last_name' => self::$adminUser->last_name,
                'source' => 'test',
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('password');

        $this->withoutMiddleware(ValidateSignature::class)
            ->postJson(self::$endpoint, [
                'first_name' => self::$adminUser->first_name,
                'last_name' => self::$adminUser->last_name,
                'password' => '123456789Aa$$',
                'source' => 'test',
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('password');
    }
}
