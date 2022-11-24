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

class OrderIndexTest extends TestCase
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
    public function test_that_un_auth_user_cant_index_orders(): void
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->getJson('api/v1/lender/orders')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_can_index_orders(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->getJson('api/v1/lender/orders')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    [
                        'phone_country_code' => self::$order->phoneNumberCountryCode,
                        'phone_number' => self::$order->mobileDialingPhoneNumber,
                        'phone_number_formatted' => self::$order->phone_number->formatInternational(),
                        'id' => self::$order->id,
                        'status' => [
                            'description' => self::$order->status->description,
                            'value' => self::$order->status->value,
                        ],
                        'company_id' => self::$company->id,
                        'reference_number' => self::$order->reference_number,
                        'national_id' => (int) self::$order->national_id,
                        'amount' => self::$order->amount,
                        'selling_price' => self::$order->selling_price,
                        'is_approved' => self::$order->approved_at !== null,
                        'status_reason' => self::$order->status_reason,
                    ],
                ],
                'meta' => [
                    'pagination' => [
                        'total' => 1,
                        'count' => 1,
                        'per_page' => 10,
                        'current_page' => 1,
                        'total_pages' => 1,
                        'links' => [],
                    ],
                ],
            ]);
    }
}
