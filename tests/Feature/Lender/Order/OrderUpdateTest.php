<?php

namespace Tests\Feature\Lender\Order;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Traits\Test\OrderTrait;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class OrderUpdateTest extends TestCase
{
    use RefreshDatabase, OrderTrait;

    private static Company $company;

    private static User $userLender;

    private static Wallet $wallet;

    private static Builder|Model $order;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompanyDetails();
        self::$userLender = $this->createLenderUser(self::$company->getOriginal('id'), Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->getOriginal('id'), self::$userLender->getOriginal('id'));
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_update_order(): void
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson('api/v1/lender/orders/'.self::$order->getOriginal('id'))
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_can_update_order(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson('api/v1/lender/orders/'.self::$order->getOriginal('id'), [
                'national_id' => '2553451234',
                'amount' => '200',
                'selling_price' => '300',
                'phone_country_code' => 'SA',
                'phone_number' => '500112233',
            ])->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'phone_country_code' => self::$order->phoneNumberCountryCode,
                    'phone_number' => self::$order->mobileDialingPhoneNumber,
                    'phone_number_formatted' => self::$order->phone_number->formatInternational(),
                    'id' => self::$order->getOriginal('id'),
                    'status' => [
                        'description' => self::$order->status->description,
                        'value' => self::$order->status->value,
                    ],
                    'company_id' => self::$company->getOriginal('id'),
                    'reference_number' => self::$order->reference_number,
                    'national_id' => self::$order->national_id,
                    'amount' => (string) self::$order->amount,
                    'selling_price' => (string) self::$order->refresh()->selling_price,
                    'contract' => '',
                    'power_of_attorney' => '',
                    'is_approved' => self::$order->approved_at !== null,
                    'status_reason' => self::$order->status_reason,
                ],
            ]);
    }
}
