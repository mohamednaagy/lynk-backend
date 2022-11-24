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

class OrderShowTest extends TestCase
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
    public function testThatUnAuthUserCantShowOrder(): void
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->getJson('api/v1/lender/orders/'.self::$order->getOriginal('id'))
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function testThatAuthUserCanShowOrder(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->getJson('api/v1/lender/orders/'.self::$order->getOriginal('id'))
            ->assertStatus(Response::HTTP_OK)
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
                    'national_id' => (int) self::$order->national_id,
                    'amount' => self::$order->amount,
                    'selling_price' => self::$order->selling_price,
                    'contract' => '',
                    'power_of_attorney' => '',
                    'is_approved' => self::$order->approved_at !== null,
                    'status_reason' => self::$order->status_reason,
                    'creator' => [
                        'id' => self::$userLender->getOriginal('id'),
                        'name' => self::$userLender->getOriginal('first_name').' '.self::$userLender->getOriginal('last_name'),
                    ],
                    'approver' => null,
                    'history' => [
                        [
                            'step' => 'client_wakala',
                            'is_complete' => false,
                            'completed_at' => null,
                            'document' => null,
                        ],
                        [
                            'step' => 'commodity_purchased',
                            'is_complete' => false,
                            'completed_at' => null,
                            'cert_document' => [
                                'url' => null, 'date' => null,
                            ],
                            'ownership_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                        ],
                        [
                            'step' => 'contract_singed',
                            'is_complete' => false,
                            'completed_at' => null,
                        ],
                        [
                            'step' => 'selling_commodity_to_customer',
                            'is_complete' => false,
                            'completed_at' => null,
                            'document' => null,
                        ],
                        [
                            'step' => 'selling_commodity_to_open_market',
                            'is_complete' => false,
                            'completed_at' => null,
                            'mpo_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                            'warranty_document' => [
                                'url' => null,
                                'date' => null,
                            ],
                        ],
                        [
                            'step' => 'murabha_sale_completed',
                            'is_complete' => false,
                            'completed_at' => null,
                        ],
                    ],
                ],
            ]);
    }
}
