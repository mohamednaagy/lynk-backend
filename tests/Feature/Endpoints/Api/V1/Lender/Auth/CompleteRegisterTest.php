<?php

namespace Endpoints\Api\V1\Lender\Auth;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ValidateSignature;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class CompleteRegisterTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    private static User $userLender;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com', [
            'password' => null,
            'email_verified_at' => null,
        ]);
    }

    /**
     * @return void
     */
    public function test_complete_register_success(): void
    {
        $this->assertFalse(self::$userLender->hasVerifiedEmail());
        $this->assertNull(self::$userLender->password);

        $this->withoutMiddleware(ValidateSignature::class)
            ->postJson('api/v1/lender/'.self::$userLender->id.'/complete-register', [
                'first_name' => self::$userLender->first_name,
                'last_name' => self::$userLender->last_name,
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
        self::$userLender = self::$userLender->refresh();
        $this->assertTrue(self::$userLender->hasVerifiedEmail());
        $this->assertNotNull(self::$userLender->password);

        $this->withoutMiddleware(ValidateSignature::class)
            ->postJson('api/v1/lender/'.self::$userLender->id.'/complete-register', [
                'first_name' => self::$userLender->first_name,
                'last_name' => self::$userLender->last_name,
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
    public function test_complete_register_fail_without_signature(): void
    {
        $this->postJson('api/v1/lender/'.self::$userLender->id.'/complete-register', [
            'first_name' => self::$userLender->first_name,
            'last_name' => self::$userLender->last_name,
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
    public function test_complete_register_validation_rules(): void
    {
        $this->withoutMiddleware(ValidateSignature::class)
            ->postJson('api/v1/lender/'.self::$userLender->id.'/complete-register', [
                'password' => '123456789Aa$$',
                'password_confirmation' => '123456789Aa$$',
                'source' => 'test',
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('first_name')
            ->assertJsonValidationErrorFor('last_name');

        $this->withoutMiddleware(ValidateSignature::class)
            ->postJson('api/v1/lender/'.self::$userLender->id.'/complete-register', [
                'first_name' => self::$userLender->first_name,
                'last_name' => self::$userLender->last_name,
                'source' => 'test',
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('password');

        $this->withoutMiddleware(ValidateSignature::class)
            ->postJson('api/v1/lender/'.self::$userLender->id.'/complete-register', [
                'first_name' => self::$userLender->first_name,
                'last_name' => self::$userLender->last_name,
                'password' => '123456789Aa$$',
                'source' => 'test',
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('password');
    }
}
